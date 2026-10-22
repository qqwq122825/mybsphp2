<?php

namespace App\Http\Controllers;

use App\Cards\CardCredentialHasher;
use App\Cards\CardOperationLogger;
use App\Install\InstallationManager;
use App\Models\AuditLog;
use App\Models\Card;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;

final class AdminCardOperationLogController extends Controller
{
    public function index(Request $request, string $adminPath, InstallationManager $installation, CardCredentialHasher $hasher): View
    {
        $query = AuditLog::query()->with('actorAdministrator')->where(function ($query): void {
            $query->where('action', 'card.operation')->orWhere(function ($legacy): void {
                $legacy->where('target_type', 'card')->whereIn('action', array_keys(CardOperationLogger::LEGACY_ACTIONS))
                    // Keep historical proxy actions, but suppress the old duplicate of a new snapshot.
                    ->whereNotExists(function ($canonical): void {
                        $canonical->selectRaw('1')->from('audit_logs as canonical')
                            ->where('canonical.action', 'card.operation')
                            ->whereColumn('canonical.request_id', 'audit_logs.request_id')
                            ->whereColumn('canonical.target_id', 'audit_logs.target_id');
                    });
            });
        });
        $keyword = trim((string) $request->query('keyword', ''));
        $field = (string) $request->query('search_field', 'credential');
        if ($keyword !== '') {
            if ($field === 'actor') {
                $query->where(fn ($q) => $q->where('after_data->actor_username', 'like', '%'.$keyword.'%')
                    ->orWhereHas('actorAdministrator', fn ($actor) => $actor->where('username', 'like', '%'.$keyword.'%')));
            } elseif ($field === 'operation') {
                $legacyActions = array_keys(array_filter(CardOperationLogger::LEGACY_ACTIONS, fn ($label) => str_contains($label, $keyword)));
                $query->where(fn ($q) => $q->where('after_data->operation', 'like', '%'.$keyword.'%')
                    ->orWhereIn('action', $legacyActions));
            } else {
                $cards = Card::query();
                $hash = $hasher->lookupHash($keyword);
                $field === 'card_id'
                    ? $cards->where('id', ctype_digit($keyword) ? (int) $keyword : 0)
                    : $cards->where('code_lookup_hash', $hash);
                $ids = $cards->pluck('public_id');
                $query->where(function ($q) use ($ids, $hash, $field, $keyword): void {
                    $q->whereIn('target_id', $ids);
                    if ($field === 'card_id') {
                        $q->orWhere('after_data->identity->id', ctype_digit($keyword) ? (int) $keyword : 0);
                    } else {
                        $q->orWhere('after_data->identity->lookup_hash', $hash)->orWhere('target_id', $keyword);
                    }
                });
            }
        }
        $logs = $query->orderBy('id', $request->query('sort') === 'oldest' ? 'asc' : 'desc')->paginate(30)->withQueryString();
        $cards = Card::query()->with('application')->whereIn('public_id', $logs->pluck('target_id'))->get()->keyBy('public_id');
        $rows = $logs->map(function (AuditLog $log) use ($cards): array {
            $card = $cards->get($log->target_id);
            $identity = data_get($log->after_data, 'identity', []);
            $credential = $card?->code_ciphertext;
            if (! $credential && ! empty($identity['credential_ciphertext'])) {
                try {
                    $credential = Crypt::decryptString($identity['credential_ciphertext']);
                } catch (DecryptException) {
                    $credential = '卡号密文暂未解密';
                }
            }
            $before = data_get($log->before_data, 'state');
            $after = data_get($log->after_data, 'state');
            $changes = [];
            if ($log->action === 'card.operation') {
                foreach (array_unique(array_merge(array_keys($before ?? []), array_keys($after ?? []))) as $key) {
                    if (($before[$key] ?? null) !== ($after[$key] ?? null)) {
                        $changes[] = $key;
                    }
                }
                if ($before === null || $after === null) {
                    $changes = ['status', 'card_is_frozen', 'user_status', 'activated_at', 'expires_at', 'activation_count'];
                }
            }

            return [
                'log' => $log, 'credential' => $credential ?: $log->target_id,
                'card_id' => $identity['id'] ?? $card?->id,
                'application' => $identity['application_name'] ?? $card?->application->name ?? '历史软件',
                'actor' => data_get($log->after_data, 'actor_username') ?: match ($log->actor_role_snapshot) {
                    'client' => '客户端 / 自动处理', 'system' => '系统', default => $log->actorDisplayName(),
                },
                'role' => match ($log->actor_role_snapshot) {
                    'agent' => '代理', 'super_admin' => '超级管理员', 'admin' => '管理员',
                    'client' => '客户端 / 自动处理', 'system' => '系统', default => $log->actor_role_snapshot ?: '历史记录',
                },
                'operation' => data_get($log->after_data, 'operation') ?: (CardOperationLogger::LEGACY_ACTIONS[$log->action] ?? $log->action),
                'before' => $this->stateLines($before, $changes, $log->action !== 'card.operation'),
                'after' => $this->stateLines($after, $changes, $log->action !== 'card.operation'),
            ];
        });

        return view('admin.logs.card-operations', [
            'appName' => $installation->applicationName(), 'adminPath' => $adminPath,
            'administrator' => $request->user('admin'), 'databaseHealthy' => true,
            'title' => '卡操作日志', 'logs' => $logs, 'rows' => $rows,
        ]);
    }

    /** @param array<string, mixed>|null $state
     * @param  list<string>  $changes
     * @return list<string>
     */
    private function stateLines(?array $state, array $changes, bool $legacy): array
    {
        if ($legacy) {
            return ['历史记录未保存前后快照'];
        }
        if ($state === null) {
            return ['卡记录不存在'];
        }
        if ($changes === []) {
            return ['状态未变化'];
        }
        $labels = [
            'status' => '使用状态', 'card_is_frozen' => '卡冻结', 'user_status' => '软件用户状态',
            'expires_at' => '到期时间', 'activated_at' => '激活时间', 'issued_at' => '发出时间',
            'consumed_at' => '充值时间', 'frozen_at' => '卡冻结时间', 'user_frozen_at' => '用户冻结时间',
            'revoked_at' => '撤销时间', 'activation_count' => '激活次数', 'bound_device_mask' => '绑定设备',
            'binding_fingerprint' => '设备指纹', 'credential_fingerprint' => '卡号指纹',
            'user_note' => '备注', 'agent_starred' => '代理标记', 'created_at' => '制作时间',
            'account_concurrency_limit' => '账号多开', 'machine_concurrency_limit' => '机器多开',
            'redeemed_by_account_id' => '充值账号编号', 'card_type_id' => '卡种编号',
            'application_id' => '软件编号', 'batch_id' => '批次编号',
            'is_timer' => '计时卡', 'timer_paused_at' => '冻结计时时间', 'timer_pause_count' => '已冻结次数',
            'timer_pause_limit' => '冻结次数上限', 'timer_pause_max_seconds' => '单次最长冻结（秒）',
            'type_duration_seconds' => '卡种时长（秒）', 'type_duration_value' => '卡种时长', 'type_duration_unit' => '卡种时间单位',
            'type_benefit_kind' => '卡种权益', 'type_face_price_cents' => '销售价（分）', 'type_default_agent_price_cents' => '代理价（分）',
        ];
        $statuses = ['inventory' => '未使用', 'issued' => '已发出', 'active' => '正常 / 已启用',
            'frozen' => '已冻结', 'expired' => '已到期', 'revoked' => '已撤销', 'consumed' => '已充值使用'];

        return array_map(function ($key) use ($state, $labels, $statuses): string {
            $value = $state[$key] ?? null;
            if (is_bool($value)) {
                $value = $value ? '是' : '否';
            }
            if (in_array($key, ['status', 'user_status'], true)) {
                $value = $statuses[$value ?? ''] ?? $value;
            }
            if (str_ends_with($key, '_fingerprint') && is_string($value)) {
                $value = substr($value, 0, 16);
            }
            if (str_ends_with($key, '_at') && is_string($value) && $value !== '') {
                $value = CarbonImmutable::parse($value)->setTimezone(config('app.timezone', 'UTC'))->format('Y-m-d H:i:s');
            }

            return ($labels[$key] ?? $key).'：'.($value === null || $value === '' ? '—' : $value);
        }, $changes);
    }
}
