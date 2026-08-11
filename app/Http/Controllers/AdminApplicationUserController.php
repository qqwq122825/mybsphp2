<?php

namespace App\Http\Controllers;

use App\Cards\CardCredentialHasher;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\Application;
use App\Models\ApplicationAccount;
use App\Models\Card;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class AdminApplicationUserController extends Controller
{
    public function __construct(
        private readonly InstallationManager $installation,
        private readonly CardCredentialHasher $credentialHasher,
    ) {}

    public function index(Request $request, string $adminPath, Application $application): View
    {
        $userType = $application->mode === 'account_recharge' ? 'account' : 'card';
        $keyword = trim((string) $request->query('keyword', ''));
        $sort = $request->input('sort') === 'oldest' ? 'asc' : 'desc';

        if ($userType === 'card') {
            $query = $application->cards()
                ->with('cardType')
                ->whereNotNull('activated_at')
                ->orderBy('id', $sort);
            if ($keyword !== '') {
                $query->where(function ($builder) use ($keyword): void {
                    if (mb_strlen($keyword) > 4) {
                        $builder->where('code_lookup_hash', $this->credentialHasher->lookupHash($keyword))
                            ->orWhere('public_id', 'like', '%'.$keyword.'%');
                    } else {
                        $builder->where('code_last4', 'like', '%'.$keyword.'%')
                            ->orWhere('public_id', 'like', '%'.$keyword.'%');
                    }
                });
            }
            $users = $query->paginate(30)->withQueryString();
        } else {
            $query = $application->accounts()->orderBy('id', $sort);
            if ($keyword !== '') {
                $query->where('username', 'like', '%'.$keyword.'%');
            }
            $users = $query->paginate(30)->withQueryString();
        }

        return view('admin.applications.users', [
            ...$this->layout($adminPath),
            'application' => $application,
            'users' => $users,
            'userType' => $userType,
        ]);
    }

    public function bulkAction(Request $request, string $adminPath, Application $application): RedirectResponse
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer'],
            'action' => ['required', Rule::in(['freeze', 'unfreeze', 'delete'])],
        ]);

        if ($application->mode === 'card_login') {
            $users = $application->cards()->whereNotNull('activated_at')->whereKey($validated['user_ids'])->get();
            foreach ($users as $card) {
                match ($validated['action']) {
                    'freeze' => $card->forceFill(['user_status' => 'frozen', 'user_frozen_at' => now()])->save(),
                    'unfreeze' => $card->forceFill(['user_status' => 'active', 'user_frozen_at' => null])->save(),
                    'delete' => $card->forceFill(['user_status' => 'revoked'])->save(),
                };
            }
        } else {
            $accounts = $application->accounts()->whereKey($validated['user_ids'])->get();
            foreach ($accounts as $account) {
                $account->forceFill([
                    'status' => match ($validated['action']) {
                        'freeze' => 'frozen', 'unfreeze' => 'active', 'delete' => 'revoked',
                    },
                ])->save();
            }
        }

        return back()->with('success', match ($validated['action']) {
            'freeze' => '所选软件用户已冻结，后续登录将被拦截。',
            'unfreeze' => '所选软件用户已解冻。',
            'delete' => '所选软件用户已删除。',
        });
    }

    public function maintenance(string $adminPath): View
    {
        return view('admin.applications.user-maintenance', [
            ...$this->layout($adminPath),
            'applications' => Application::query()->orderBy('name')->get(),
        ]);
    }

    public function runMaintenance(Request $request, string $adminPath): RedirectResponse
    {
        $validated = $request->validate([
            'selection_mode' => ['required', Rule::in(['ids', 'filter'])],
            'id_list' => ['nullable', 'string', 'max:20000'],
            'application_id' => ['required', 'integer', 'exists:applications,id'],
            'scope' => ['required', Rule::in(['all', 'not_expired', 'expired', 'expires_within', 'uid', 'binding', 'expiry', 'added_time'])],
            'scope_value' => ['nullable', 'string', 'max:160'],
            'action' => ['required', Rule::in(['add_time', 'subtract_time', 'freeze', 'unfreeze', 'set_expiry', 'bind_feature', 'delete'])],
            'action_value' => ['nullable', 'string', 'max:160'],
            'confirmed' => ['accepted'],
        ]);

        if ($validated['selection_mode'] === 'ids' && ! filled($validated['id_list'] ?? null)) {
            throw ValidationException::withMessages(['id_list' => '请填写用户索引 ID。']);
        }
        if ($validated['selection_mode'] === 'filter' && in_array($validated['scope'], ['expires_within', 'uid', 'binding', 'expiry', 'added_time'], true) && ! filled($validated['scope_value'] ?? null)) {
            throw ValidationException::withMessages(['scope_value' => '当前维护范围需要填写条件值。']);
        }
        if (in_array($validated['action'], ['add_time', 'subtract_time', 'set_expiry', 'bind_feature'], true) && ! filled($validated['action_value'] ?? null)) {
            throw ValidationException::withMessages(['action_value' => '当前维护功能需要填写 N 值。']);
        }

        $application = Application::query()->findOrFail((int) $validated['application_id']);
        $isCard = $application->mode === 'card_login';
        $query = $isCard
            ? $application->cards()->whereNotNull('activated_at')
            : $application->accounts();

        if ($validated['selection_mode'] === 'ids') {
            $query->whereKey($this->parseIds((string) $validated['id_list']));
        } else {
            $scopeValue = trim((string) ($validated['scope_value'] ?? ''));
            match ($validated['scope']) {
                'not_expired' => $query->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now())),
                'expired' => $query->whereNotNull('expires_at')->where('expires_at', '<=', now()),
                'expires_within' => $query->whereBetween('expires_at', [now(), now()->addDays($this->boundedInteger($scopeValue, 1, 36500, '到期天数'))]),
                'uid' => $query->where($isCard ? 'public_id' : 'username', 'like', '%'.$scopeValue.'%'),
                'binding' => $query->where('bound_device_mask', 'like', '%'.$scopeValue.'%'),
                'expiry' => $query->where('expires_at', $this->dateValue($scopeValue)),
                'added_time' => $query->whereDate('created_at', $this->dateValue($scopeValue)->toDateString()),
                default => $query,
            };
        }

        $affected = (clone $query)->count();
        if ($affected === 0) {
            return back()->withErrors(['selection_mode' => '当前条件没有匹配到软件用户。'])->withInput();
        }

        $action = $validated['action'];
        $value = trim((string) ($validated['action_value'] ?? ''));
        $seconds = in_array($action, ['add_time', 'subtract_time'], true)
            ? $this->boundedInteger($value, 1, 315360000, '时间秒数') : 0;
        $expiry = $action === 'set_expiry' ? $this->dateValue($value) : null;

        DB::transaction(function () use ($query, $isCard, $action, $value, $seconds, $expiry): void {
            $query->orderBy('id')->chunkById(500, function ($users) use ($isCard, $action, $value, $seconds, $expiry): void {
                foreach ($users as $user) {
                    $base = $user->expires_at && $user->expires_at->isFuture() ? $user->expires_at->copy() : now();
                    match ($action) {
                        'add_time' => $user->forceFill(['expires_at' => $base->addSeconds($seconds)])->save(),
                        'subtract_time' => $user->forceFill(['expires_at' => $base->subSeconds($seconds)])->save(),
                        'freeze' => $user->forceFill($isCard
                            ? ['user_status' => 'frozen', 'user_frozen_at' => now()]
                            : ['status' => 'frozen'])->save(),
                        'unfreeze' => $user->forceFill($isCard
                            ? ['user_status' => 'active', 'user_frozen_at' => null]
                            : ['status' => 'active'])->save(),
                        'set_expiry' => $user->forceFill(['expires_at' => $expiry])->save(),
                        'bind_feature' => $user->forceFill(['bound_device_ciphertext' => $value, 'bound_device_mask' => $value])->save(),
                        'delete' => $user->forceFill($isCard ? ['user_status' => 'revoked'] : ['status' => 'revoked'])->save(),
                    };
                }
            });
        }, 3);

        return back()->with('success', "软件用户批量维护完成：共处理 {$affected} 个用户。");
    }

    /** @return list<int> */
    private function parseIds(string $value): array
    {
        $ids = [];
        foreach (preg_split('/[\s,;，；]+/u', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $part) {
            if (preg_match('/^(\d+)-(\d+)$/', $part, $matches)) {
                $start = (int) $matches[1];
                $end = (int) $matches[2];
                if ($start < 1 || $end < $start || $end - $start > 10000) {
                    throw ValidationException::withMessages(['id_list' => "ID 范围 {$part} 格式错误或跨度过大。"]);
                }
                foreach (range($start, $end) as $id) {
                    $ids[$id] = $id;
                }
            } elseif (ctype_digit($part) && (int) $part > 0) {
                $ids[(int) $part] = (int) $part;
            } else {
                throw ValidationException::withMessages(['id_list' => "ID {$part} 格式错误。"]);
            }
        }
        if ($ids === [] || count($ids) > 100000) {
            throw ValidationException::withMessages(['id_list' => '请填写 1 至 100000 个有效用户 ID。']);
        }

        return array_values($ids);
    }

    private function boundedInteger(string $value, int $min, int $max, string $label): int
    {
        if (! ctype_digit($value) || (int) $value < $min || (int) $value > $max) {
            throw ValidationException::withMessages(['action_value' => "{$label}需填写 {$min} 至 {$max} 的整数。"]);
        }

        return (int) $value;
    }

    private function dateValue(string $value): CarbonImmutable
    {
        try {
            return ctype_digit($value) ? CarbonImmutable::createFromTimestamp((int) $value) : CarbonImmutable::parse($value);
        } catch (\Throwable) {
            throw ValidationException::withMessages(['action_value' => '时间需填写日期时间或 Unix 时间戳。']);
        }
    }

    public function updateCard(Request $request, string $adminPath, Application $application, Card $card): RedirectResponse
    {
        abort_unless($application->mode === 'card_login' && $card->application_id === $application->getKey() && $card->activated_at, 404);
        $validated = $this->validateDetails($request);
        $expiresAt = $this->expiryAfterRebindDeduction($application, $validated['expires_at'] ?: $card->expires_at, $card->bound_device_ciphertext, $validated['bound_device']);
        $card->forceFill([
            'expires_at' => $expiresAt ?? ($validated['expires_at'] ?: null),
            'bound_device_ciphertext' => $validated['bound_device'] ?: null,
            'bound_device_mask' => $validated['bound_device'] ?: null,
            'account_concurrency_limit' => $validated['account_concurrency_limit'],
            'machine_concurrency_limit' => $validated['machine_concurrency_limit'],
            'user_note' => $validated['note'] ?: null,
        ])->save();

        return back()->with('success', '卡串用户资料已保存。');
    }

    public function updateAccount(Request $request, string $adminPath, Application $application, ApplicationAccount $account): RedirectResponse
    {
        abort_unless($application->mode === 'account_recharge' && $account->application_id === $application->getKey(), 404);
        $validated = $this->validateDetails($request);
        $expiresAt = $this->expiryAfterRebindDeduction($application, $validated['expires_at'] ?: $account->expires_at, $account->bound_device_ciphertext, $validated['bound_device']);
        $account->forceFill([
            'expires_at' => $expiresAt ?? ($validated['expires_at'] ?: null),
            'bound_device_ciphertext' => $validated['bound_device'] ?: null,
            'bound_device_mask' => $validated['bound_device'] ?: null,
            'account_concurrency_limit' => $validated['account_concurrency_limit'],
            'machine_concurrency_limit' => $validated['machine_concurrency_limit'],
            'note' => $validated['note'] ?: null,
        ])->save();

        return back()->with('success', '账号用户资料已保存。');
    }

    private function expiryAfterRebindDeduction(Application $application, mixed $currentExpiry, mixed $oldBinding, mixed $newBinding): mixed
    {
        $oldBinding = trim((string) $oldBinding);
        $newBinding = trim((string) $newBinding);
        $seconds = $application->rebindDeductionSeconds();

        if ($seconds === 0 || $oldBinding === '' || hash_equals($oldBinding, $newBinding)) {
            return null;
        }

        $base = $currentExpiry ? CarbonImmutable::parse($currentExpiry) : now()->toImmutable();

        return $base->subSeconds($seconds);
    }

    /** @return array<string, mixed> */
    private function validateDetails(Request $request): array
    {
        return $request->validate([
            'expires_at' => ['nullable', 'date'],
            'bound_device' => ['nullable', 'string', 'max:160'],
            'account_concurrency_limit' => ['required', 'integer', 'min:-1', 'max:100'],
            'machine_concurrency_limit' => ['required', 'integer', 'min:-1', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
    }

    /** @return array<string, mixed> */
    private function layout(string $adminPath): array
    {
        return [
            'appName' => $this->installation->applicationName(),
            'adminPath' => $adminPath,
            'administrator' => $this->administrator(),
            'databaseHealthy' => true,
        ];
    }

    private function administrator(): Administrator
    {
        $administrator = Auth::guard('admin')->user();
        abort_unless($administrator instanceof Administrator, 401);

        return $administrator;
    }
}
