<?php

namespace Tests\Feature;

use App\Applications\ApplicationCredentialService;
use App\Applications\HybridPacketCipher;
use App\Cards\CardGenerationService;
use App\Models\Administrator;
use App\Models\Application;
use App\Models\CardType;
use App\Models\ClientSecurityEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ClientIntegrityIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string, string, bool, string|null}> */
    public static function loginCases(): array
    {
        return [
            'crc before session' => ['enforce', '{"cm":1}', true, 'CRC'],
            'existing structural verdict' => ['enforce', '{"cm":0,"tm":1}', true, 'T0'],
            'decoy counter alone' => ['enforce', '{"dc":1}', true, 'D0'],
            'clean' => ['enforce', '{"cm":0}', false, null],
            'legacy missing' => ['enforce', '', false, null],
            'legacy malformed' => ['enforce', '{', false, null],
            'unknown result' => ['enforce', '{"cm":null}', false, null],
            'real slot' => ['enforce', '{"c":1,"s":[9]}', false, 'I0'],
            'observe crc' => ['observe', '{"cm":1}', false, 'CRC'],
            'observe structural' => ['observe', '{"tm":1}', false, 'T0'],
            'disabled' => ['disabled', '{"cm":1,"tm":1}', false, null],
        ];
    }

    #[DataProvider('loginCases')]
    public function test_compact_login_policy(string $mode, string $hint, bool $blocked, ?string $event): void
    {
        [$app, $card] = $this->fixture($mode);
        $response = $this->exchange($app, $this->login($card, $hint));
        if ($blocked) {
            $this->assertSame('INVALID_CARD', $response['error']['code']);
            $this->assertSame('卡密不存在', $response['error']['message']);
            $this->assertDatabaseCount('application_client_sessions', 0);
            $this->assertDatabaseHas('cards', ['application_id' => $app->id, 'status' => 'inventory']);
        } else {
            $this->assertSame('01', explode('|', $response['plain'])[0]);
            $this->assertDatabaseHas('application_client_sessions', ['status' => 'active']);
        }
        if ($event) {
            $this->assertDatabaseHas('client_security_events', [
                'event_code' => $event, 'action' => $blocked ? 'freeze' : 'observe',
            ]);
        } else {
            $this->assertDatabaseCount('client_security_events', 0);
        }
    }

    /** @return array<string, array{string, string, array<string, mixed>, string}> */
    public static function reportCases(): array
    {
        return [
            'late crc' => ['enforce', 'CRC', ['kind' => 'text', 'cm' => 1], 'freeze'],
            'typed I0 crc' => ['enforce', 'I0', ['kind' => 'text', 'cm' => 1], 'freeze'],
            'typed structural' => ['enforce', 'T0', ['kind' => 'text', 'tm' => 1], 'freeze'],
            'old I0' => ['enforce', 'I0', ['kind' => 'text'], 'observe'],
            'old T0' => ['enforce', 'T0', ['kind' => 'text', 'rx' => 1], 'observe'],
            'real slot D0' => ['enforce', 'D0', ['slot' => 9, 'kind' => 'code'], 'observe'],
            'decoy D0' => ['enforce', 'D0', ['slot' => 2, 'kind' => 'imp'], 'freeze'],
            'observe crc' => ['observe', 'CRC', ['kind' => 'text', 'cm' => 1], 'observe'],
            'observe structural' => ['observe', 'T0', ['kind' => 'text', 'tm' => 1], 'observe'],
        ];
    }

    /** @param array<string, mixed> $evidence */
    #[DataProvider('reportCases')]
    public function test_delayed_report_ack_and_heartbeat(string $mode, string $code, array $evidence, string $action): void
    {
        [$app, $card] = $this->fixture($mode);
        $login = explode('|', $this->exchange($app, $this->login($card))['plain']);
        $parameters = $this->base('security.report') + [
            'event_id' => 'integration-report-0001', 'event_code' => $code,
            'report_token' => $login[10], 'evidence' => json_encode($evidence),
        ];
        for ($i = 0; $i < 2; $i++) {
            $fields = explode('|', $this->exchange($app, $parameters)['plain']);
            $this->assertSame(['01', 'accepted', 'INTEGRATION-SAFE'], array_slice($fields, 0, 3));
            $this->assertSame($action, $fields[4]);
        }
        $this->assertDatabaseCount('client_security_events', 1);
        $heartbeat = explode('|', $this->exchange($app, $this->base('timeout.ic'))['plain']);
        $this->assertSame($action === 'freeze' ? '5026' : '5031', $heartbeat[0]);
        $this->assertCount(10, $heartbeat);
    }

    /** @return list<array{string}> */
    public static function lateHints(): array
    {
        return [['{"cm":1}'], ['{"tm":1}'], ['{"dc":1}']];
    }

    #[DataProvider('lateHints')]
    public function test_heartbeat_closes_crc_timeout_window(string $hint): void
    {
        [$app, $card] = $this->fixture('enforce');
        $this->exchange($app, $this->login($card)); // early scan still pending
        $heartbeat = explode('|', $this->exchange($app, $this->base('timeout.ic') + ['ih' => $hint])['plain']);
        $this->assertSame('5026', $heartbeat[0]);
        $this->assertSame('', $heartbeat[4]); // no fresh report token for denied session
        $this->assertDatabaseHas('application_client_sessions', ['status' => 'security_blocked']);
        $this->assertSame('heartbeat.ih', ClientSecurityEvent::query()->sole()->evidence['source']);
    }

    public function test_global_device_block_reaches_other_apps_and_omitted_heartbeat_key(): void
    {
        [$first, $firstCard] = $this->fixture('enforce');
        [$second, $secondCard] = $this->fixture('observe', '202');
        $this->exchange($first, $this->login($firstCard));
        $this->exchange($second, $this->login($secondCard));
        $blocked = $this->exchange($first, [...$this->login($firstCard, '{"cm":1}'), 'BSphpSeSsL' => str_repeat('b', 64)]);
        $this->assertSame('INVALID_CARD', $blocked['error']['code']);
        $heartbeat = $this->base('timeout.ic');
        unset($heartbeat['key']);
        $fields = explode('|', $this->exchange($second, $heartbeat)['plain']);
        $this->assertSame('5026', $fields[0]);
    }

    public function test_wrong_device_heartbeat_has_no_blocking_side_effect(): void
    {
        [$app, $card] = $this->fixture('enforce');
        $this->exchange($app, $this->login($card));
        $parameters = [...$this->base('timeout.ic'), 'key' => 'UNRELATED-DEVICE', 'ih' => '{"cm":1}'];
        $this->assertSame('5026', explode('|', $this->exchange($app, $parameters)['plain'])[0]);
        $this->assertDatabaseCount('client_security_events', 0);
        $this->assertDatabaseCount('global_security_blocks', 0);
        $this->assertDatabaseHas('application_client_sessions', ['status' => 'active']);
    }

    public function test_report_binding_and_rotated_token_are_checked_before_state_change(): void
    {
        [$app, $card] = $this->fixture('enforce');
        $login = explode('|', $this->exchange($app, $this->login($card))['plain']);
        $heartbeat = explode('|', $this->exchange($app, $this->base('timeout.ic'))['plain']);
        $report = $this->base('security.report') + [
            'event_id' => 'integration-binding-0001', 'event_code' => 'CRC',
            'report_token' => $login[10], 'evidence' => '{"kind":"text","cm":1}',
        ];
        $this->assertArrayHasKey('error', $this->exchange($app, $report));
        $report['report_token'] = $heartbeat[4];
        $report['key'] = 'UNRELATED-DEVICE';
        $this->assertArrayHasKey('error', $this->exchange($app, $report));
        $this->assertDatabaseCount('client_security_events', 0);
        $report['key'] = 'INTEGRATION-DEVICE';
        $this->assertSame('freeze', explode('|', $this->exchange($app, $report)['plain'])[4]);
    }

    public function test_actual_objc_crypto_compact_login_and_late_crc(): void
    {
        if (! getenv('MTX_WIRE_CLIENT')) {
            $this->markTestSkipped('Set MTX_WIRE_CLIENT to the compiled MTXWireClient harness.');
        }
        [$app, $card] = $this->fixture('enforce');
        $login = explode('|', $this->exchange($app, $this->login($card, '{"cm":0}'), true)['plain']);
        $this->assertSame('01', $login[0]);
        $this->assertSame(str_repeat('a', 64), $login[9]);
        $report = $this->base('security.report') + [
            'event_id' => 'native-client-crc-00001', 'event_code' => 'CRC',
            'report_token' => $login[10], 'evidence' => '{"kind":"text","cm":1,"persona":"launcher"}',
        ];
        $this->assertSame('freeze', explode('|', $this->exchange($app, $report, true)['plain'])[4]);
        $this->assertSame('5026', explode('|', $this->exchange($app, $this->base('timeout.ic'), true)['plain'])[0]);
    }

    public function test_account_login_uses_same_integrity_policy_without_issuing_a_session(): void
    {
        [$app] = $this->fixture('enforce');
        $app->forceFill(['mode' => 'account_recharge'])->save();
        $response = $this->exchange($app, $this->base('login.lg') + [
            'user' => 'local-account', 'pwd' => 'local-password', 'ih' => '{"cm":1}',
        ]);
        $this->assertSame('INVALID_CREDENTIALS', $response['error']['code']);
        $this->assertDatabaseCount('application_client_sessions', 0);
        $this->assertDatabaseHas('client_security_events', ['event_code' => 'CRC', 'action' => 'freeze']);
    }

    public function test_event_id_does_not_acknowledge_a_different_signal(): void
    {
        [$app, $card] = $this->fixture('enforce');
        $login = explode('|', $this->exchange($app, $this->login($card))['plain']);
        $report = $this->base('security.report') + [
            'event_id' => 'same-event-different-code', 'event_code' => 'I0',
            'report_token' => $login[10], 'evidence' => '{"kind":"imp","slot":9}',
        ];
        $this->assertSame('observe', explode('|', $this->exchange($app, $report)['plain'])[4]);
        $report['event_code'] = 'CRC';
        $this->assertArrayHasKey('error', $this->exchange($app, $report));
        $this->assertDatabaseCount('client_security_events', 1);
        $this->assertDatabaseHas('application_client_sessions', ['status' => 'active']);
    }

    public function test_actual_objc_crypto_detects_response_mac_and_request_nonce_changes(): void
    {
        if (! getenv('MTX_WIRE_CLIENT')) {
            $this->markTestSkipped('Set MTX_WIRE_CLIENT to the compiled MTXWireClient harness.');
        }
        [$app] = $this->fixture('observe');
        $sealed = $this->native(['op' => 'seal', 'plain' => 'local fixture', 'public' => $app->server_public_key, 'mutual' => $app->communicationKey()]);
        $cipher = app(HybridPacketCipher::class);
        $opened = $cipher->openRequest($app, $sealed['envelope']);
        $envelope = $cipher->sealResponse('verified fixture', $opened['session_key'], $opened['request_nonce']);
        $input = ['op' => 'open', 'envelope' => $envelope, 'k' => $sealed['k'], 'n' => $sealed['n']];
        $this->assertSame('verified fixture', $this->native($input)['plain']);
        $badMac = $input;
        $badMac['envelope']['mac'][0] = $badMac['envelope']['mac'][0] === 'A' ? 'B' : 'A';
        $badNonce = $input;
        $badNonce['n'] = str_repeat('x', 22);
        foreach ([$badMac, $badNonce] as $bad) {
            $process = new Process([(string) getenv('MTX_WIRE_CLIENT')]);
            $process->setInput(json_encode($bad, JSON_THROW_ON_ERROR));
            $this->assertSame(3, $process->run());
            $this->assertSame('', $process->getOutput());
        }
    }

    /** @return array<string, string> */
    private function base(string $api): array
    {
        return ['api' => $api, 'key' => 'INTEGRATION-DEVICE', 'BSphpSeSsL' => str_repeat('a', 64), 'appsafecode' => 'INTEGRATION-SAFE'];
    }

    /**
     * @param  array<string, mixed>  $card
     * @return array<string, mixed>
     */
    private function login(array $card, string $hint = ''): array
    {
        return $this->base('login.ic') + ['icid' => $card['code'], 'ih' => $hint];
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    private function exchange(Application $app, array $parameters, bool $native = false): array
    {
        $cipher = app(HybridPacketCipher::class);
        $plain = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        $sealed = $native
            ? $this->native(['op' => 'seal', 'plain' => $plain, 'public' => $app->server_public_key, 'mutual' => $app->communicationKey()])
            : $cipher->sealRequestWithContext($app, $plain);
        $e = $sealed['envelope'];
        $packet = [3, $e['ts'], $e['nonce'], $e['ek'], $e['iv'], $e['data'], $e['mac'], $e['auth']];
        $response = $this->postJson('/AppEn.php?appid='.$app->code.'&md5='.$app->gateway_token, $packet)->json();
        if (isset($response['error'])) {
            return $response;
        }
        $this->assertCount(7, $response);
        $this->assertSame(3, $response[0]);
        $envelope = ['v' => 2, 'alg' => HybridPacketCipher::ALGORITHM, 'direction' => 'response',
            'ts' => $response[1], 'nonce' => $response[2], 'request_nonce' => $response[3],
            'iv' => $response[4], 'data' => $response[5], 'mac' => $response[6]];

        return ['plain' => $native
            ? $this->native(['op' => 'open', 'envelope' => $envelope, 'k' => $sealed['k'], 'n' => $sealed['n']])['plain']
            : $cipher->openResponse($envelope, $sealed['session_key'], $sealed['request_nonce'])];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function native(array $input): array
    {
        $process = new Process([(string) getenv('MTX_WIRE_CLIENT')]);
        $process->setInput(json_encode($input, JSON_THROW_ON_ERROR));
        $process->mustRun();

        return json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    }

    /** @return array{Application, array<string, mixed>} */
    private function fixture(string $mode, string $code = '201'): array
    {
        $credentials = app(ApplicationCredentialService::class)->generate();
        $app = Application::query()->create([
            'public_id' => (string) Str::ulid(), 'code' => $code, 'name' => 'Local integration fixture',
            'mode' => 'card_login', 'entitlement_kind' => 'duration', 'status' => 'active',
            'payload_mode' => 'constant', 'payload_value_ciphertext' => '0x100000',
            'client_secret_hash' => hash('sha256', $credentials['communication_key_ciphertext']),
            'bind_device_enabled' => true, 'max_bound_devices' => 1, 'max_concurrent_sessions' => 1, ...$credentials,
        ]);
        $app->riskPolicy()->create(['enabled' => false, 'security_report_mode' => $mode]);
        $type = CardType::query()->create([
            'public_id' => (string) Str::ulid(), 'application_id' => $app->id, 'name' => 'Local card',
            'code' => 'local', 'purpose' => 'login_credential', 'benefit_kind' => 'duration',
            'duration_seconds' => 86400, 'face_price_cents' => 0, 'code_prefix' => 'IT', 'code_length' => 20,
            'secret_length' => 0, 'status' => 'active', 'max_activations' => 1,
        ]);
        $admin = Administrator::query()->create([
            'username' => 'local-'.Str::random(8), 'password' => 'local-fixture-only', 'role' => 'super_admin', 'status' => 'active',
        ]);

        return [$app, app(CardGenerationService::class)->generate($type, 1, $admin)['credentials'][0]];
    }
}
