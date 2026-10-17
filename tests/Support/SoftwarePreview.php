<?php

namespace Tests\Support;

use App\Models\Application;
use App\Models\ApplicationAccount;
use App\Models\Card;
use App\Models\CardType;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/** Unsaved fixtures for the software management component preview. */
final class SoftwarePreview
{
    public static function render(string $page, array $common, Request $request): string
    {
        $at = CarbonImmutable::parse('2026-09-10 17:08:35');
        $applications = collect(['王者荣耀巨魔', '暗区突围国服巨魔', '三角洲国服巨魔', '账户模式演示软件'])->map(function ($name, $i) {
            $application = (new Application)->forceFill([
                'id' => 1004 - $i, 'public_id' => 'preview-software-'.($i + 1), 'name' => $name, 'code' => (string) (1004 - $i),
                'mode' => $i === 3 ? 'account_recharge' : 'card_login', 'status' => 'active',
                'cards_count' => 16 + $i * 12, 'activated_cards_count' => 15 + $i * 10, 'card_users_count' => 15 + $i * 10, 'accounts_count' => 8, 'card_types_count' => 2,
                'max_bound_devices' => 1, 'max_concurrent_sessions' => 1, 'heartbeat_interval_seconds' => 30, 'connection_timeout_seconds' => 120,
                'peer_display_enabled' => true,
                'version_name' => 'v1.0', 'description' => '本地样式预览示例软件', 'closed_message' => '系统维护，暂时关闭，请稍后再访问！',
                'encryption_mode' => 'hybrid_rsa_aes_hmac', 'communication_key_ciphertext' => 'DEMO-COMMUNICATION-KEY-NOT-FOR-USE',
                'server_public_key' => 'DEMO-PUBLIC-KEY-PREVIEW-ONLY', 'payload_value_ciphertext' => '{"message":"本地组件预览"}',
            ]);
            $application->setRelation('legacyConfig', null);

            return $application;
        });
        $accountMode = in_array($page, ['software-account-config', 'software-account-users'], true);
        $application = $applications[$accountMode ? 3 : 0];
        if (in_array($page, ['software', 'software-empty'], true)) {
            return view('admin.applications.index', $common + [
                'applications' => new LengthAwarePaginator($page === 'software-empty' ? [] : $applications, $page === 'software-empty' ? 0 : 4, 20, 1, ['path' => $request->url()]),
                'metrics' => ['total' => $page === 'software-empty' ? 0 : 4, 'active' => 4, 'card_login' => 3, 'account_recharge' => 1],
            ])->render();
        }
        if (in_array($page, ['software-config', 'software-account-config', 'software-create'], true)) {
            return view('admin.applications.form', $common + [
                'application' => $page === 'software-create' ? null : $application,
                'suggestedCode' => '12345678',
                'peerPresence' => [
                    'url' => '/admin/applications/preview-software-1/peer-presence', 'edit_url' => $request->url(),
                    'enabled' => true, 'rows' => [
                        ['game_name' => '满天星·星河', 'last_seen_at' => '2026-10-17 04:14:26'],
                        ['game_name' => '三角洲玩家_小宇', 'last_seen_at' => '2026-10-17 04:12:08'],
                        ['game_name' => '今晚一起撤离', 'last_seen_at' => '2026-10-17 04:08:35'],
                    ],
                    'total' => 3, 'page' => 1, 'per_page' => 25, 'last_page' => 1, 'pending_names' => 0, 'error' => null,
                    'refreshed_at' => '2026-10-17 04:14:41',
                ],
                'serverAddress' => 'http://127.0.0.1:8092/demo-gateway', 'objectiveCMacros' => "// 仅用于展示排版\n#define APP_CODE @\"1004\"\n#define COMM_KEY @\"DEMO-KEY\"",
            ])->render();
        }
        $type = (new CardType)->forceFill(['account_concurrency_limit' => 1, 'machine_concurrency_limit' => 1]);
        $users = collect();
        for ($i = 0; $i < 12; $i++) {
            $data = ['id' => $i + 1, 'public_id' => 'preview-user-'.($i + 1), 'application_id' => $application->id, 'expires_at' => $i === 2 ? null : $at->addDays(6 + $i), 'bound_device_ciphertext' => $i === 1 ? '3CFDD33C-C703-42F0-9E00-3B36090270EC' : 'YPWKXWDQCN', 'account_concurrency_limit' => 0, 'machine_concurrency_limit' => $i === 2 ? -1 : 0];
            if ($accountMode) {
                $user = (new ApplicationAccount)->forceFill($data + ['username' => 'demo_user_'.($i + 1), 'status' => $i === 1 ? 'frozen' : 'active', 'last_login_at' => $at->subMinutes($i * 8), 'note' => $i === 1 ? '需要检查设备绑定' : null]);
            } else {
                $user = (new Card)->forceFill($data + ['code_ciphertext' => 'DEMO-CARD-'.str_pad((string) ($i + 1), 12, '0', STR_PAD_LEFT), 'status' => $i === 3 ? 'revoked' : 'active', 'user_status' => $i === 1 ? 'frozen' : 'active', 'activated_at' => $at->subDays(1), 'last_online_at' => $at->subMinutes($i * 8), 'user_note' => $i === 1 ? '需要检查设备绑定' : null]);
                $user->setRelation('cardType', $type);
            }
            $users->push($user);
        }

        return view('admin.applications.users', $common + ['application' => $application, 'userType' => $accountMode ? 'account' : 'card', 'users' => new LengthAwarePaginator($users, 12, 30, 1, ['path' => $request->url()])])->render();
    }
}
