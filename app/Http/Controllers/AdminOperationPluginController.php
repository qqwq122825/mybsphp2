<?php

namespace App\Http\Controllers;

use App\Audit\AuditEvent;
use App\Audit\AuditLogger;
use App\Cards\CardCredentialHasher;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\AuditLog;
use App\Models\Card;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class AdminOperationPluginController extends Controller
{
    private const TOOLS = [
        'monitor' => ['接口监控调试插件', 'audit.view'],
        'batch-query' => ['批量查询', 'cards.view'],
        'batch-freeze' => ['批量封卡', 'cards.bulk_manage'],
        'batch-unfreeze' => ['批量解冻', 'cards.bulk_manage'],
        'batch-delete' => ['批量删除', 'cards.bulk_manage'],
    ];

    public function __construct(
        private readonly InstallationManager $installation,
        private readonly CardCredentialHasher $credentialHasher,
        private readonly AuditLogger $audit,
    ) {}

    public function show(Request $request, string $adminPath, string $tool): View
    {
        [$title, $permission] = self::TOOLS[$tool];
        Gate::authorize($permission);

        $codes = [];
        $queryResults = collect();
        if (in_array($tool, ['batch-query', 'batch-freeze', 'batch-unfreeze', 'batch-delete'], true)
            && $request->isMethod('post')) {
            $rules = [
                'codes' => ['required', 'string', 'max:50000'],
            ];
            if ($tool === 'batch-delete') {
                $rules['confirmed'] = ['accepted'];
            }

            $validated = $request->validate($rules);
            $codes = $this->parseCodes($validated['codes']);
            $hashes = collect($codes)->mapWithKeys(fn (string $code): array => [
                $this->credentialHasher->lookupHash($code) => $code,
            ]);
            $cards = Card::query()
                ->with(['application', 'cardType'])
                ->whereIn('code_lookup_hash', $hashes->keys())
                ->get()
                ->keyBy('code_lookup_hash');

            $affected = 0;
            $queryResults = DB::transaction(function () use ($codes, $cards, $tool, &$affected) {
                return collect($codes)->map(function (string $code) use ($cards, $tool, &$affected): array {
                    $hash = $this->credentialHasher->lookupHash($code);
                    $card = $cards->get($hash);

                    if (! $card instanceof Card) {
                        return ['code' => $code, 'card' => null, 'result' => '卡密不存在'];
                    }

                    $result = match ($tool) {
                        'batch-freeze' => $this->freeze($card, $affected),
                        'batch-unfreeze' => $this->unfreeze($card, $affected),
                        'batch-delete' => $this->revoke($card, $affected),
                        default => '查询成功',
                    };

                    $card->refresh()->loadMissing(['application', 'cardType']);

                    return ['code' => $code, 'card' => $card, 'result' => $result];
                });
            });

            if ($tool !== 'batch-query') {
                $this->audit->record(new AuditEvent(
                    action: 'operation_plugin.cards_quick_action',
                    targetType: 'cards',
                    after: [
                        'operation' => $tool,
                        'input_count' => count($codes),
                        'matched_count' => $cards->count(),
                        'affected_count' => $affected,
                    ],
                    actorRole: $this->actor()->role,
                ), $request);
            }
        }

        return view('admin.operation-plugins.show', [
            'appName' => $this->installation->applicationName(),
            'adminPath' => $adminPath,
            'administrator' => $this->actor(),
            'databaseHealthy' => true,
            'tool' => $tool,
            'title' => $title,
            'codes' => $codes,
            'queryResults' => $queryResults,
            'recentLogs' => $tool === 'monitor'
                ? AuditLog::query()->latest('id')->limit(20)->get()
                : collect(),
        ]);
    }

    private function actor(): Administrator
    {
        $administrator = Auth::guard('admin')->user();
        abort_unless($administrator instanceof Administrator, 401);

        return $administrator;
    }

    /** @return list<string> */
    private function parseCodes(string $input): array
    {
        return array_values(array_unique(array_slice(
            preg_split('/[\r\n,，;；\s]+/u', trim($input), -1, PREG_SPLIT_NO_EMPTY) ?: [],
            0,
            500,
        )));
    }

    private function freeze(Card $card, int &$affected): string
    {
        if ($card->status === 'revoked') {
            return '卡密已删除，未执行封卡';
        }
        if ($card->card_is_frozen) {
            return '卡密原本已冻结';
        }

        $card->forceFill(['card_is_frozen' => true, 'frozen_at' => now()])->save();
        $affected++;

        return '封卡成功';
    }

    private function unfreeze(Card $card, int &$affected): string
    {
        if ($card->status === 'revoked') {
            return '卡密已删除，未执行解冻';
        }
        if (! $card->card_is_frozen) {
            return '卡密当前未冻结';
        }

        $card->forceFill(['card_is_frozen' => false, 'frozen_at' => null])->save();
        $affected++;

        return '解冻成功';
    }

    private function revoke(Card $card, int &$affected): string
    {
        if ($card->status === 'revoked') {
            return '卡密原本已删除';
        }

        $card->forceFill(['status' => 'revoked', 'revoked_at' => now()])->save();
        $affected++;

        return '删除成功';
    }
}
