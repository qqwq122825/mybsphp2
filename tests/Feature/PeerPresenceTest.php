<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\SystemSetting;
use App\Presence\PeerPresenceRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class PeerPresenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('p', 32)), 'peer_presence.store' => 'array']);
        Cache::store('array')->flush();
    }

    private function software(string $code = '1002', bool $enabled = true): Application
    {
        return Application::query()->create([
            'public_id' => (string) Str::ulid(), 'code' => $code, 'name' => '三角洲国服',
            'mode' => 'card_login', 'entitlement_kind' => 'time', 'client_secret_hash' => 'fixture',
            'communication_key_ciphertext' => 'peer-test-key', 'peer_display_enabled' => $enabled,
        ]);
    }

    private function packet(Application $app, string $operation, array $data = [], ?string $nonce = null, ?int $time = null)
    {
        $path = '/api/v2/software/'.$app->code.'/peer-presence/'.$operation;
        $body = $operation === 'config' ? '' : json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $method = $operation === 'config' ? 'GET' : 'POST';
        $stamp = (string) ($time ?? now()->timestamp);
        $nonce ??= (string) Str::uuid();
        $signature = hash_hmac('sha256', $method."\n".$path."\n".$stamp."\n".$nonce."\n".$body, 'peer-test-key');
        $response = $this->call($method, $path, [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_PEER_TIME' => $stamp, 'HTTP_X_PEER_NONCE' => $nonce, 'HTTP_X_PEER_SIGNATURE' => $signature,
        ], $body);
        if ($response->status() === 200) {
            $this->assertSame(hash_hmac('sha256', $stamp."\n".$nonce."\n".$response->getContent(), 'peer-test-key'), $response->headers->get('X-Peer-Response-Signature'));
        }

        return $response;
    }

    public function test_config_defaults_off_and_is_independent_from_license_service(): void
    {
        $app = $this->software(enabled: false);
        SystemSetting::query()->updateOrCreate(['key' => 'client_service_enabled'], ['value' => false]);
        $this->packet($app, 'config')->assertOk()->assertJsonPath('data.enabled', false)->assertJsonPath('data.heartbeat_seconds', 600);
        $app->update(['peer_display_enabled' => true]);
        $this->packet($app, 'config')->assertOk()->assertJsonPath('data.enabled', true)->assertJsonPath('data.ttl_seconds', 1200);
        $this->assertDatabaseCount('application_client_sessions', 0);
    }

    public function test_heartbeat_roster_is_software_scoped_hashes_only_and_expires_at_twenty_minutes(): void
    {
        $app = $this->software(); $other = $this->software('1003');
        $a = (string) Str::uuid(); $b = (string) Str::uuid();
        $name = '  我的三角洲昵称  ';
        $this->packet($app, 'heartbeat', ['installation_id' => $a, 'game_name' => $name])->assertOk()->assertJsonPath('data.peers', []);
        $this->travel(10)->minutes();
        $response = $this->packet($app, 'heartbeat', ['installation_id' => $b, 'game_name' => '另一个玩家']);
        $response->assertOk()->assertJsonPath('data.peers.0.digest', PeerPresenceRegistry::digest('1002', $name))->assertJsonPath('data.peers.0.expires_in', 600);
        $this->assertStringNotContainsString('我的三角洲昵称', $response->getContent());
        $this->packet($other, 'heartbeat', ['installation_id' => (string) Str::uuid(), 'game_name' => '其他软件'])->assertJsonPath('data.peers', []);
        $this->travel(10)->minutes();
        $this->packet($app, 'heartbeat', ['installation_id' => $b, 'game_name' => '另一个玩家'])->assertJsonPath('data.peers', []);
    }

    public function test_rename_leave_and_disable_clear_presence(): void
    {
        $app = $this->software();$a = (string) Str::uuid();$b = (string) Str::uuid();
        $this->packet($app, 'heartbeat', ['installation_id' => $a, 'game_name' => '旧名字']);
        $this->packet($app, 'heartbeat', ['installation_id' => $a, 'game_name' => '新名字']);
        $this->packet($app, 'heartbeat', ['installation_id' => $b, 'game_name' => '观察者'])->assertJsonCount(1, 'data.peers')->assertJsonPath('data.peers.0.digest', PeerPresenceRegistry::digest('1002', '新名字'));
        $this->packet($app, 'leave', ['installation_id' => $a])->assertOk();
        $this->packet($app, 'heartbeat', ['installation_id' => $b, 'game_name' => '观察者'])->assertJsonPath('data.peers', []);
        $app->update(['peer_display_enabled' => false]);
        $this->packet($app, 'heartbeat', ['installation_id' => $a, 'game_name' => '禁用时不上线'])->assertJsonPath('data.enabled', false)->assertJsonPath('data.peers', []);
        $app->update(['peer_display_enabled' => true]);
        $this->packet($app, 'heartbeat', ['installation_id' => $b, 'game_name' => '观察者'])->assertJsonPath('data.peers', []);
    }

    public function test_signed_packets_reject_forgery_replay_stale_clock_and_bad_input(): void
    {
        $app = $this->software();$nonce = (string) Str::uuid();
        $this->packet($app, 'config', nonce: $nonce)->assertOk();
        $this->packet($app, 'config', nonce: $nonce)->assertStatus(409);
        $this->packet($app, 'config', time: now()->timestamp - 301)->assertUnauthorized();
        $this->getJson('/api/v2/software/1002/peer-presence/config')->assertUnauthorized();
        $this->packet($app, 'heartbeat', ['installation_id' => 'bad', 'game_name' => 'A'])->assertUnprocessable();
        $this->packet($app, 'heartbeat', ['installation_id' => (string) Str::uuid(), 'game_name' => '   '])->assertUnprocessable();
        $this->packet($app, 'heartbeat', ['installation_id' => (string) Str::uuid(), 'game_name' => str_repeat('中', 50)])->assertStatus(422);
        $this->packet($app, 'heartbeat', ['installation_id' => (string) Str::uuid(), 'game_name' => "\0名字"])->assertStatus(422);
        $this->packet($app, 'heartbeat', ['installation_id' => (string) Str::uuid(), 'game_name' => "名\0字"])->assertStatus(422);
    }

    public function test_migration_default_and_admin_panel_are_wired(): void
    {
        $app = $this->software(enabled: false);
        $this->assertFalse($app->fresh()->peer_display_enabled);
        $form = file_get_contents(resource_path('views/admin/applications/form.blade.php'));
        $this->assertStringContainsString('data-config-tab="entertainment"', $form);
        $this->assertStringContainsString('name="peer_display_enabled"', $form);
        $this->assertStringContainsString('娱乐设置', $form);
        $this->assertStringContainsString("'peer_display_enabled' => ['sometimes', 'boolean']", file_get_contents(app_path('Http/Controllers/AdminApplicationController.php')));
    }
    private function v2(Application $app, string $id, string $md5 = '', string $name = '自己')
    {
        return $this->packet($app, 'heartbeat', ['installation_id' => $id, 'game_name' => $name,
            'roster_protocol' => 2, 'roster_md5' => $md5]);
    }

    public function test_md5_unchanged_omits_roster_and_lease_renewal_does_not_change_version(): void
    {
        $app = $this->software();$a = (string) Str::uuid();$b = (string) Str::uuid();
        $this->packet($app, 'heartbeat', ['installation_id' => $a, 'game_name' => '同行']);
        $first = $this->v2($app, $b)->assertOk()->assertJsonPath('data.roster_updated', true)
            ->assertJsonPath('data.roster', PeerPresenceRegistry::digest('1002', '同行'));
        $md5 = $first->json('data.roster_md5');
        $this->assertSame(md5("mtx-peer-roster:v2:1002:\n".$first->json('data.roster')), $md5);
        $this->travel(10)->minutes();
        $this->packet($app, 'heartbeat', ['installation_id' => $a, 'game_name' => '同行']);
        $same = $this->v2($app, $b, $md5)->assertOk()->assertJsonPath('data.roster_updated', false)
            ->assertJsonPath('data.roster_md5', $md5)->assertJsonPath('data.roster_ttl_seconds', 1200);
        $this->assertArrayNotHasKey('roster', $same->json('data'));
        $this->assertArrayNotHasKey('peers', $same->json('data'));
        // At 1200 seconds after the peer's last heartbeat it must be removed before MD5 comparison.
        $this->travel(20)->minutes();
        $empty = $this->v2($app, $b, $md5)->assertOk()->assertJsonPath('data.roster_updated', true)->assertJsonPath('data.roster', '');
        $this->v2($app, $b, $empty->json('data.roster_md5'))->assertJsonPath('data.roster_updated', false);
    }

    public function test_md5_is_sorted_unique_software_scoped_and_tracks_rename_leave_and_disable(): void
    {
        $app = $this->software();$other = $this->software('1003');
        $a=(string)Str::uuid();$b=(string)Str::uuid();$c=(string)Str::uuid();
        $this->packet($app, 'heartbeat', ['installation_id'=>$a,'game_name'=>'重复']);
        $this->packet($app, 'heartbeat', ['installation_id'=>$b,'game_name'=>'重复']);
        $one=$this->v2($app,$c)->assertJsonPath('data.roster',PeerPresenceRegistry::digest('1002','重复'));
        $md5=$one->json('data.roster_md5');
        $this->packet($app,'heartbeat',['installation_id'=>$a,'game_name'=>'新名字']);
        $two=$this->v2($app,$c,$md5)->assertJsonPath('data.roster_updated',true);
        $lines=explode("\n",$two->json('data.roster'));$sorted=$lines;sort($sorted,SORT_STRING);
        $this->assertSame($sorted,$lines);$this->assertCount(2,$lines);
        $this->packet($app,'leave',['installation_id'=>$a]);
        $this->v2($app,$c,$two->json('data.roster_md5'))->assertJsonPath('data.roster_updated',true)->assertJsonPath('data.roster_md5',$md5);
        $isolated=$this->v2($other,(string)Str::uuid(),$md5)->assertJsonPath('data.roster_updated',true)->assertJsonPath('data.roster','');
        $this->assertNotSame($md5,$isolated->json('data.roster_md5'));
        $app->update(['peer_display_enabled'=>false]);
        $this->v2($app,$c,$md5)->assertJsonPath('data.enabled',false);
        $app->update(['peer_display_enabled'=>true]);
        $this->v2($app,$c,$md5)->assertJsonPath('data.roster_updated',true)->assertJsonPath('data.roster','');
        // Hash canonicalization ignores ordering and per-entry expiry.
        $this->assertSame(PeerPresenceRegistry::snapshot('1002',[
            ['digest'=>'b','expires_in'=>1],['digest'=>'a','expires_in'=>2],['digest'=>'b','expires_in'=>3],
        ]),PeerPresenceRegistry::snapshot('1002',[
            ['digest'=>'a','expires_in'=>1200],['digest'=>'b','expires_in'=>600],
        ]));
    }

    public function test_large_roster_unchanged_response_stays_small_and_bad_versions_are_rejected(): void
    {
        $app=$this->software();$rows=[];
        for($i=0;$i<4095;$i++)$rows[(string)Str::uuid()]=['digest'=>PeerPresenceRegistry::digest('1002','Player'.$i),'expires'=>now()->timestamp+1200];
        Cache::store('array')->put('peer-presence:v1:'.$app->id,$rows,1200);
        $id=(string)Str::uuid();$large=$this->v2($app,$id)->assertOk()->assertJsonPath('data.roster_updated',true);
        $this->assertSame(4095,count(explode("\n",$large->json('data.roster'))));
        $this->assertGreaterThan(260000,strlen($large->getContent()));
        $same=$this->v2($app,$id,$large->json('data.roster_md5'))->assertOk()->assertJsonPath('data.roster_updated',false);
        $this->assertLessThan(512,strlen($same->getContent()));
        fwrite(STDOUT,"MD5 bandwidth: full=".strlen($large->getContent())." bytes; unchanged=".strlen($same->getContent())." bytes; peers=4095\n");
        $this->v2($app,$id,'bad')->assertUnprocessable();
        $this->v2($app,$id,str_repeat('A',32))->assertUnprocessable();
        $this->packet($app,'heartbeat',['installation_id'=>$id,'game_name'=>'自己','roster_protocol'=>3])->assertUnprocessable();
        // Bad/missing cache hash forces full recovery instead of an unchanged response.
        $this->v2($app,$id,str_repeat('0',32))->assertJsonPath('data.roster_updated',true);
    }

}
