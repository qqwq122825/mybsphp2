<?php

namespace App\Http\Controllers;

use App\Audit\AuditEvent;
use App\Audit\AuditLogger;
use App\Cards\CardCredentialHasher;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\ApplicationAccount;
use App\Models\ApplicationClientSession;
use App\Models\Card;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class AdminQuickOperationController extends Controller
{
    public function __construct(
        private readonly InstallationManager $installation,
        private readonly CardCredentialHasher $hasher,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request, string $adminPath): View
    {
        $card = null;
        $publicId = trim((string) $request->query('card', ''));
        if ($publicId !== '') {
            $card = $this->cardQuery()->where('public_id', $publicId)->first();
        }

        return view('admin.quick-operations.index', [
            ...$this->layout($adminPath),
            'card' => $card,
            'target' => $card instanceof Card ? $this->target($card) : null,
        ]);
    }

    public function search(Request $request, string $adminPath): RedirectResponse
    {
        $validated = $request->validate([
            'credential' => ['required', 'string', 'min:3', 'max:255'],
        ]);
        $credential = trim($validated['credential']);
        $card = $this->cardQuery()
            ->where('code_lookup_hash', $this->hasher->lookupHash($credential))
            ->first();

        if (! $card instanceof Card) {
            throw ValidationException::withMessages([
                'credential' => '没有找到这张充值卡，请核对完整卡号。',
            ]);
        }

        return redirect()->route('admin.quick-operations.index', [
            'adminPath' => $adminPath,
            'card' => $card->public_id,
        ]);
    }

    public function action(Request $request, string $adminPath): RedirectResponse
    {
        $validated = $request->validate([
            'card_id' => ['required', 'integer', 'exists:cards,id'],
            'action' => ['required', Rule::in([
                'add_time', 'unbind_user', 'freeze_user', 'unfreeze_user', 'delete_user', 'delete_card',
            ])],
            'days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'confirmed' => ['sometimes', 'accepted'],
        ]);
        $action = $validated['action'];
        $days = (int) ($validated['days'] ?? 0);
        if ($action === 'add_time' && $days < 1) {
            throw ValidationException::withMessages(['days' => '请填写要增加的天数。']);
        }
        if (in_array($action, ['delete_user', 'delete_card'], true) && ! $request->boolean('confirmed')) {
            throw ValidationException::withMessages(['confirmed' => '删除操作需要勾选确认。']);
        }

        $cardPublicId = '';
        $applicationId = null;
        $before = [];
        $after = [];
        $deleted = false;
        DB::transaction(function () use (
            $validated,
            $action,
            $days,
            &$cardPublicId,
            &$applicationId,
            &$before,
            &$after,
            &$deleted,
        ): void {
            $card = $this->cardQuery()
                ->lockForUpdate()
                ->findOrFail((int) $validated['card_id']);
            $target = $this->target($card);
            $cardPublicId = (string) $card->public_id;
            $applicationId = (int) $card->application_id;
            $before = [
                'card_status' => $card->status,
                'card_expires_at' => $card->expires_at?->toIso8601String(),
                'target_type' => $target instanceof ApplicationAccount ? 'account' : ($target instanceof Card ? 'card_user' : null),
                'target_status' => $target instanceof Card ? $target->user_status : $target?->status,
                'target_expires_at' => $target?->expires_at?->toIso8601String(),
                'target_bound_device' => $target?->bound_device_mask,
                'active_sessions' => $this->activeSessionCount($target),
            ];

            match ($action) {
                'add_time' => $this->addTime($target, $days),
                'unbind_user' => $this->unbind($target),
                'freeze_user' => $this->freeze($target),
                'unfreeze_user' => $this->unfreeze($target),
                'delete_user' => $this->deleteUser($target),
                'delete_card' => $deleted = $this->deleteCard($card),
            };

            if (! $deleted) {
                $card->refresh();
                $target = $this->target($card);
                $after = [
                    'action' => $action,
                    'days' => $action === 'add_time' ? $days : null,
                    'card_status' => $card->status,
                    'card_expires_at' => $card->expires_at?->toIso8601String(),
                    'target_status' => $target instanceof Card ? $target->user_status : $target?->status,
                    'target_expires_at' => $target?->expires_at?->toIso8601String(),
                    'target_bound_device' => $target?->bound_device_mask,
                    'active_sessions' => $this->activeSessionCount($target),
                ];
            } else {
                $after = ['action' => $action, 'card_deleted' => true];
            }
        }, 3);

        $this->audit->record(new AuditEvent(
            action: 'quick_operation.executed',
            targetType: 'card',
            targetId: $cardPublicId,
            applicationId: $applicationId,
            before: $before,
            after: $after,
            actorRole: $this->administrator()->role,
        ), $request);

        $message = match ($action) {
            'add_time' => "已为用户增加 {$days} 天时间。",
            'unbind_user' => '用户设备绑定已解除，现有登录连接已失效。',
            'freeze_user' => '用户已冻结。',
            'unfreeze_user' => '用户已恢复正常。',
            'delete_user' => '用户已删除并停止继续登录。',
            'delete_card' => $deleted ? '未激活充值卡已删除。' : '充值卡已作废，历史记录继续保留。',
        };

        return redirect()->route('admin.quick-operations.index', array_filter([
            'adminPath' => $adminPath,
            'card' => $deleted ? null : $cardPublicId,
        ]))->with('success', $message);
    }

    private function cardQuery()
    {
        return Card::query()->with([
            'application',
            'cardType',
            'batch',
            'redeemedAccount',
        ]);
    }

    private function target(Card $card): Card|ApplicationAccount|null
    {
        if ($card->application?->mode === 'card_login') {
            return $card->activated_at ? $card : null;
        }

        return $card->redeemedAccount;
    }

    private function addTime(Card|ApplicationAccount|null $target, int $days): void
    {
        if (! $target) {
            throw ValidationException::withMessages(['action' => '这张充值卡尚未关联软件用户，暂时没有可加时的对象。']);
        }
        $base = $target->expires_at && $target->expires_at->isFuture()
            ? $target->expires_at->copy()
            : now();
        $values = ['expires_at' => $base->addDays($days)];
        if ($target instanceof Card) {
            $values += ['status' => 'active', 'user_status' => 'active', 'revoked_at' => null];
        } else {
            $values += ['status' => 'active'];
        }
        $target->forceFill($values)->save();
    }

    private function freeze(Card|ApplicationAccount|null $target): void
    {
        if (! $target instanceof ApplicationAccount) {
            throw ValidationException::withMessages(['action' => '充值卡用户请使用解绑或删卡操作。']);
        }
        $target->forceFill(['status' => 'frozen'])->save();
    }

    private function unfreeze(Card|ApplicationAccount|null $target): void
    {
        if (! $target instanceof ApplicationAccount) {
            throw ValidationException::withMessages(['action' => '充值卡用户请使用解绑或删卡操作。']);
        }
        $target->forceFill(['status' => 'active'])->save();
    }

    private function unbind(Card|ApplicationAccount|null $target): void
    {
        if (! $target instanceof Card && ! $target instanceof ApplicationAccount) {
            throw ValidationException::withMessages(['action' => '当前充值卡尚未关联可解绑的软件用户。']);
        }
        $target->forceFill([
            'bound_device_ciphertext' => null,
            'bound_device_mask' => null,
        ])->save();
        $this->closeActiveSessions($target);
    }

    private function deleteUser(Card|ApplicationAccount|null $target): void
    {
        if (! $target instanceof ApplicationAccount) {
            throw ValidationException::withMessages(['action' => '充值卡用户请直接删除/作废充值卡。']);
        }
        $target->forceFill(['status' => 'revoked'])->save();
        $this->closeActiveSessions($target);
    }

    private function activeSessionCount(Card|ApplicationAccount|null $target): int
    {
        if (! $target) {
            return 0;
        }

        return ApplicationClientSession::query()
            ->where($target instanceof Card ? 'card_id' : 'application_account_id', $target->getKey())
            ->where('status', 'active')
            ->count();
    }

    private function closeActiveSessions(Card|ApplicationAccount $target): void
    {
        ApplicationClientSession::query()
            ->where($target instanceof Card ? 'card_id' : 'application_account_id', $target->getKey())
            ->where('status', 'active')
            ->update([
                'status' => 'logged_out',
                'logged_out_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function deleteCard(Card $card): bool
    {
        if ($card->application?->mode === 'account_recharge' && $card->redeemed_by_account_id) {
            throw ValidationException::withMessages([
                'action' => '这张充值卡已经充值到账号，卡密将作为充值历史记录保留。',
            ]);
        }
        if (in_array($card->status, ['inventory', 'issued'], true) && ! $card->activated_at && ! $card->redeemed_by_account_id) {
            $card->delete();

            return true;
        }
        $card->forceFill([
            'status' => 'revoked',
            'user_status' => $card->application?->mode === 'card_login' ? 'revoked' : $card->user_status,
            'revoked_at' => now(),
        ])->save();

        return false;
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
