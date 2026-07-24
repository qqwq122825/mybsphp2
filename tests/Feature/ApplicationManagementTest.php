<?php

namespace Tests\Feature;

use App\Auth\SystemAccessSynchronizer;
use App\Cards\CardGenerationService;
use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\Application;
use App\Models\ApplicationAccount;
use App\Models\Card;
use App\Models\CardBatch;
use App\Models\CardType;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApplicationManagementTest extends TestCase
{
    use RefreshDatabase;

    private string $lockPath;

    protected function setUp(): void
    {
        parent::setUp();

        app(SystemAccessSynchronizer::class)->sync();
        $this->lockPath = storage_path('framework/testing/application-management.lock');
        @unlink($this->lockPath);
        config([
            'mybsphp.installed' => false,
            'mybsphp.install_lock' => $this->lockPath,
        ]);
        app(InstallationManager::class)->writeLock([
            'admin_path' => 'private-console',
            'app_name' => 'MyBsPHP2 Apps',
            'secondary_password_enabled' => false,
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->lockPath);
        parent::tearDown();
    }

    public function test_operation_plugins_have_a_first_level_menu_and_working_tool_pages(): void
    {
        $administrator = $this->administrator();
        $this->actingAs($administrator, 'admin');

        $this->get('/private-console/operation-plugins/batch-query')
            ->assertOk()
            ->assertSee('运营插件')
            ->assertSee('接口监控调试插件')
            ->assertSee('批量查询')
            ->assertSee('批量封卡')
            ->assertSee('批量解冻')
            ->assertSee('批量删除')
            ->assertDontSee('添加 App 用户')
            ->assertSee('需要查询的充值卡');

        $this->get('/private-console/operation-plugins/add-app-user')->assertNotFound();

        $this->post('/private-console/operation-plugins/batch-query', [
            'codes' => "NOT-FOUND-001\nNOT-FOUND-002",
        ])->assertOk()->assertSee('不存在');

        $this->get('/private-console/operation-plugins/batch-freeze')
            ->assertOk()
            ->assertSee('需要封卡的充值卡')
            ->assertSee('系统会根据卡密自动识别所属软件并立即冻结')
            ->assertDontSee('进入批量封卡');

        $this->post('/private-console/operation-plugins/batch-freeze', [
            'codes' => 'NOT-FOUND-003',
        ])->assertOk()->assertSee('卡密不存在');

        $this->get('/private-console/operation-plugins/monitor')
            ->assertOk()
            ->assertSee('最近接口与后台操作记录');
    }

    public function test_deleting_a_card_type_removes_unactivated_cards_but_retains_used_cards(): void
    {
        $administrator = $this->administrator();
        $this->actingAs($administrator, 'admin');

        $this->post('/private-console/applications', [
            'code' => '20000002',
            'name' => '浏览器测试软件',
            'mode' => 'card_login',
        ])->assertRedirect();
        $application = Application::query()->where('code', '20000002')->sole();

        $this->post('/private-console/card-types', [
            'application_id' => $application->getKey(),
            'name' => '浏览器密码测试卡',
            'code_prefix' => 'WEB',
            'note' => '待删除类型',
            'sort_order' => 0,
            'duration_value' => 1,
            'duration_unit' => 'day',
            'face_price' => 1,
            'agent_price' => 1,
            'balance_recharge' => 0,
            'code_length' => 25,
            'code_charset' => 'mixed',
            'secret_length' => 8,
            'secret_charset' => 'mixed',
            'account_concurrency_limit' => 0,
            'machine_concurrency_limit' => 0,
        ])->assertRedirect();
        $cardType = $application->cardTypes()->sole();
        $generated = app(CardGenerationService::class)->generate($cardType, 2, $administrator, '删除测试');
        $batchId = $generated['batch']->getKey();
        $cardIds = $generated['batch']->cards()->pluck('id')->all();
        Card::query()->whereKey($cardIds[0])->update([
            'status' => 'active',
            'activated_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        $this->from('/private-console/applications/'.$application->public_id.'/card-types')
            ->delete('/private-console/card-types', [
                'application_id' => $application->getKey(),
                'card_type_ids' => [$cardType->getKey()],
            ])
            ->assertRedirect('/private-console/applications/'.$application->public_id.'/card-types')
            ->assertSessionHas('success', '已删除 1 个卡串类型，清理 1 张未激活充值卡；保留 1 个已删除类型下的已激活或已使用卡供查询。保留明细：浏览器密码测试卡（1 张已激活或已使用卡）。');

        $this->assertSoftDeleted('card_types', ['id' => $cardType->getKey()]);
        $this->assertDatabaseHas('card_batches', ['id' => $batchId, 'deleted_at' => null]);
        $this->assertDatabaseHas('cards', ['id' => $cardIds[0], 'status' => 'active']);
        $this->assertDatabaseMissing('cards', ['id' => $cardIds[1]]);
        $this->post('/activation-query', ['credential' => $generated['credentials'][0]['code']])
            ->assertOk()
            ->assertSee('浏览器密码测试卡')
            ->assertSee('已激活')
            ->assertSee('到期时间');
        $this->postJson('/api/v2/demo/activate', [
            'code' => $generated['credentials'][0]['code'],
            'secret' => $generated['credentials'][0]['secret'],
            'device_id' => 'DELETE-TYPE-QUERY-DEVICE',
        ])->assertOk()->assertJsonPath('data.card.status', 'active');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'card_types.deleted',
            'target_id' => $application->public_id,
        ]);
    }

    public function test_super_administrator_can_create_edit_and_disable_an_application(): void
    {
        $administrator = $this->administrator();
        $this->actingAs($administrator, 'admin');

        $this->get('/private-console/applications/create')
            ->assertOk()
            ->assertSee('页面标签导航')
            ->assertSee('快捷入口指引')
            ->assertSee('切换侧栏显示')
            ->assertSee('data-quick-entry', false)
            ->assertSee('代理后端')
            ->assertSee('/agent/index.php', false)
            ->assertSee('激活码查询')
            ->assertSee('/activation-query', false)
            ->assertSee('mybsphp.css?v=20260725-custom-select', false)
            ->assertSee('mybsphp.js?v=20260725-custom-select', false)
            ->assertDontSee('后台入口')
            ->assertSee('软件管理')
            ->assertSee('添加新软件')
            ->assertSee('软件模式')
            ->assertSee('软件名称')
            ->assertSee('软件代号')
            ->assertDontSee('授权返回与设备')
            ->assertDontSee('启用设备绑定')
            ->assertDontSee('验证数据');

        $this->from('/private-console/applications/create')
            ->post('/private-console/applications', [
                'code' => 'game_ios',
                'name' => '非法代号软件',
                'mode' => 'card_login',
            ])
            ->assertRedirect('/private-console/applications/create')
            ->assertSessionHasErrors('code');

        $response = $this->post('/private-console/applications', [
            'code' => '10000001',
            'name' => '游戏 iOS',
            'mode' => 'card_login',
        ]);

        $application = Application::query()->where('code', '10000001')->sole();
        $response
            ->assertRedirect('/private-console/applications/'.$application->public_id.'/edit')
            ->assertSessionHas('client_secret', $application->communicationKey());

        $this->assertSame('active', $application->status);
        $this->assertSame('constant', $application->payload_mode);
        $this->assertSame('', $application->verificationData());
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $application->communicationKey());
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', (string) $application->gateway_token);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/]+={0,2}$/', (string) $application->server_public_key);
        $this->assertStringNotContainsString('BEGIN RSA PUBLIC KEY', (string) $application->server_public_key);
        $this->assertStringContainsString('BEGIN PRIVATE KEY', $application->serverPrivateKey());
        $this->assertSame(18, strlen($application->legacyPacketKey()));
        $this->assertSame('hybrid_rsa_aes_hmac', $application->encryption_mode);
        $this->assertDatabaseHas('application_legacy_configs', [
            'application_id' => $application->getKey(),
            'login_api_name' => 'login.ic',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'application.created',
            'target_id' => $application->public_id,
        ]);

        $this->get('/private-console/applications')
            ->assertOk()
            ->assertSee('游戏 iOS')
            ->assertSee('卡串验证限时模式')
            ->assertSee('软件配置')
            ->assertSee('封控设置')
            ->assertSee('/private-console/risk-control?application='.$application->public_id, false)
            ->assertSee('data-workspace-title="'.$application->code.'-封控设置"', false)
            ->assertSee('用户管理')
            ->assertSee('充值卡列表')
            ->assertSee('充值卡批量维护')
            ->assertDontSee('授权协议配置')
            ->assertSee('卡串类型')
            ->assertSee('现卡制作')
            ->assertSee('删除软件')
            ->assertSee('data-window-dialog', false)
            ->assertSee('data-window-open="delete-application-'.$application->id.'"', false)
            ->assertSee('data-window-scope="workspace"', false)
            ->assertDontSee('data-window-auto-open', false)
            ->assertDontSee('data-window-action="minimize"', false)
            ->assertSee('data-window-action="maximize"', false)
            ->assertSee('当前软件卡')
            ->assertSee('当前软件分类')
            ->assertSee('name="delete_application" value="1" checked', false)
            ->assertSee('勾选“当前软件”时会自动全选')
            ->assertDontSee('删除库卡')
            ->assertSee('data-workspace-open', false)
            ->assertSee('data-workspace-base-link', false)
            ->assertSee('data-workspace-base-tab', false)
            ->assertSee('data-workspace-base-url=', false)
            ->assertSee('mybsphp.js?v=20260725-custom-select', false)
            ->assertSee($application->code.'-卡串类型');

        $this->get('/private-console/applications/'.$application->public_id.'/users')
            ->assertOk()
            ->assertSee('software-user-table-component', false)
            ->assertSee('admin-data-table-toolbar', false)
            ->assertSee('卡串验证模式')
            ->assertDontSee('账号用户')
            ->assertDontSee('用户分组');

        $this->get('/private-console/applications/'.$application->public_id.'/cards/make')
            ->assertOk()
            ->assertSee('请先添加充值卡类型')
            ->assertDontSee('确认制作');

        $this->get('/private-console/applications/'.$application->public_id.'/card-types')
            ->assertOk()
            ->assertSee('card-type-table-component', false)
            ->assertSee('data-workspace-static-close', false)
            ->assertSee('充值卡类型')
            ->assertSee('添加新充值卡类型')
            ->assertSee('快速添加模拟卡类型')
            ->assertSee('删除选中')
            ->assertSee('card-type-workspace-card', false)
            ->assertDontSee('CARD TYPES')
            ->assertDontSee('当前页是软件内部工作页')
            ->assertDontSee('返回软件管理')
            ->assertDontSee('全部卡密')
            ->assertDontSee('累计生成')
            ->assertDontSee('用户分组')
            ->assertDontSee('转至用户组');

        $this->get('/private-console/applications/'.$application->public_id.'/card-types?create=1')
            ->assertOk()
            ->assertSee('添加充值卡类型')
            ->assertSee('data-card-type-editor-dialog', false)
            ->assertSee('data-window-auto-open', false)
            ->assertSee('data-window-scope="workspace"', false)
            ->assertDontSee('data-window-action="minimize"', false)
            ->assertSee('data-window-action="maximize"', false)
            ->assertSee('data-window-action="close"', false)
            ->assertSee('card-type-table-component', false)
            ->assertSee('代号：'.$application->code)
            ->assertDontSee('卡类图片')
            ->assertDontSee('类型代号')
            ->assertSee('充值卡别名')
            ->assertSee('开头字符')
            ->assertSee('余额充值')
            ->assertSee('卡号字符类型')
            ->assertDontSee('密码字符类型')
            ->assertSee('账号同时在线数')
            ->assertSee('机器同时使用量')
            ->assertSee('value="25"', false)
            ->assertSee('value="0"', false);

        $this->post('/private-console/card-types', [
            'application_id' => $application->getKey(),
            'name' => '贵宾年卡',
            'code_prefix' => 'jGG',
            'note' => '内部测试类型',
            'sort_order' => 20,
            'duration_value' => 1,
            'duration_unit' => 'year',
            'face_price' => -1,
            'agent_price' => 88.5,
            'balance_recharge' => -5,
            'code_length' => 35,
            'code_charset' => 'mixed',
            'secret_length' => 0,
            'secret_charset' => 'digits',
            'account_concurrency_limit' => 0,
            'machine_concurrency_limit' => -1,
        ])->assertRedirect('/private-console/applications/'.$application->public_id.'/card-types');

        $cardType = CardType::query()->where('application_id', $application->getKey())->sole();
        $this->assertMatchesRegularExpression('/^ct_[0-9a-z]{12}$/', $cardType->code);
        $this->assertSame('jGG', $cardType->code_prefix);
        $this->assertSame(20, $cardType->sort_order);
        $this->assertSame(1, $cardType->duration_value);
        $this->assertSame('year', $cardType->duration_unit);
        $this->assertSame(31536000, $cardType->duration_seconds);
        $this->assertSame(-100, $cardType->face_price_cents);
        $this->assertSame(8850, $cardType->default_agent_price_cents);
        $this->assertSame(-500, $cardType->balance_recharge_cents);
        $this->assertSame(35, $cardType->code_length);
        $this->assertSame(0, $cardType->secret_length);
        $this->assertSame(-1, $cardType->machine_concurrency_limit);

        $this->get('/private-console/applications/'.$application->public_id.'/card-types?edit='.$cardType->public_id)
            ->assertOk()->assertSee('编辑充值卡类型')->assertSee('贵宾年卡')->assertDontSee('用户分组');
        $this->put('/private-console/card-types/'.$cardType->public_id, [
            'application_id' => $application->getKey(), 'name' => '贵宾年卡', 'code_prefix' => 'jGG',
            'note' => '已编辑备注', 'sort_order' => 20, 'duration_value' => 1, 'duration_unit' => 'year',
            'face_price' => -1, 'agent_price' => 88.5, 'balance_recharge' => -5,
            'code_length' => 35, 'code_charset' => 'mixed', 'secret_length' => 0,
            'secret_charset' => 'digits', 'account_concurrency_limit' => 0, 'machine_concurrency_limit' => -1,
        ])->assertRedirect('/private-console/applications/'.$application->public_id.'/card-types');
        $this->assertSame('已编辑备注', $cardType->fresh()->note);

        $this->get('/private-console/applications/'.$application->public_id.'/card-types')
            ->assertOk()->assertSee('已编辑备注')->assertSee('卡号组成')->assertDontSee('密码组成')
            ->assertSee('账号多开')->assertSee('机器用量')->assertSee('编辑')->assertDontSee('转至用户组');

        $generated = app(CardGenerationService::class)->generate($cardType, 1, $administrator, '零密码类型测试');
        $this->assertSame(35, strlen($generated['credentials'][0]['code']));
        $this->assertStringStartsWith('jGG', $generated['credentials'][0]['code']);
        $this->assertSame('', $generated['credentials'][0]['secret']);

        $quickActionCard = $generated['batch']->cards()->sole();
        $this->post('/private-console/operation-plugins/batch-freeze', [
            'codes' => $generated['credentials'][0]['code'],
        ])->assertOk()
            ->assertSee('封卡成功')
            ->assertSee($application->name)
            ->assertSee('自动识别的软件');
        $this->assertTrue($quickActionCard->fresh()->card_is_frozen);

        $this->post('/private-console/operation-plugins/batch-unfreeze', [
            'codes' => $generated['credentials'][0]['code'],
        ])->assertOk()->assertSee('解冻成功')->assertSee($application->name);
        $this->assertFalse($quickActionCard->fresh()->card_is_frozen);

        $this->get('/private-console/applications/'.$application->public_id.'/cards/make')
            ->assertOk()
            ->assertSee('现卡制作')
            ->assertSee('所属软件')
            ->assertSee('卡串类型')
            ->assertSee('制卡方式')
            ->assertSee('自动生成')
            ->assertSee('导入卡号')
            ->assertSee('制作数量')
            ->assertDontSee('卡销售标识')
            ->assertSee('制卡备注')
            ->assertSee('制卡者')
            ->assertDontSee('MAKE CARDS')
            ->assertDontSee('返回软件管理');

        $this->post('/private-console/card-batches', [
            'card_type_id' => $cardType->getKey(),
            'generation_mode' => 'automatic',
            'quantity' => 2,
            'sale_flag' => 'sale',
            'note' => '新版 EUI 制卡',
            'maker_reference' => 'operator-1001',
        ])->assertRedirect('/private-console/applications/'.$application->public_id.'/cards/make');

        $batch = CardBatch::query()->latest('id')->firstOrFail();
        $this->assertSame('sale', $batch->sale_flag);
        $this->assertSame('operator-1001', $batch->maker_reference);

        $this->get('/private-console/applications/'.$application->public_id.'/cards/make')
            ->assertOk()
            ->assertSee('导出充值卡 - 软件：'.$application->code)
            ->assertSee('复制全部')
            ->assertSee('单卡号')
            ->assertSee('卡号+密码')
            ->assertSee('Tab分隔')
            ->assertSee('CSV')
            ->assertSee('继续制卡')
            ->assertSee('{KAID}-----')
            ->assertSee('-----')
            ->assertDontSee('卡密批次生成成功，明文仅在本次页面展示。')
            ->assertDontSee($batch->batch_no);

        $this->get('/private-console/cards?application='.$application->getKey())
            ->assertOk()
            ->assertSee('recharge-card-table-component', false)
            ->assertSee('admin-data-table-toolbar', false)
            ->assertSee('admin-data-table-scroll', false);

        $this->get('/private-console/system-logs/card-making')
            ->assertOk()
            ->assertSee('代理制卡日志')
            ->assertSee('后台制卡成功，后台管理员UID:'.$administrator->getKey().'，2张')
            ->assertDontSee('{&quot;batch_no&quot;', false);

        $maintenanceCard = $cardType->cards()->oldest('id')->firstOrFail();
        $this->get('/private-console/cards/maintenance')
            ->assertOk()
            ->assertSee('A. ID 批量处理')
            ->assertSee('B. 筛选批量处理')
            ->assertSee('设置充值天数为 N')
            ->assertSee('删除卡串（设为已作废）')
            ->assertDontSee('授权协议配置');
        $this->from('/private-console/cards/maintenance')->post('/private-console/cards/maintenance', [
            'selection_mode' => 'ids', 'id_list' => (string) $maintenanceCard->getKey(),
            'scope' => 'all', 'action' => 'freeze', 'confirmed' => '1',
        ])->assertRedirect('/private-console/cards/maintenance');
        $this->assertTrue($maintenanceCard->fresh()->card_is_frozen);

        $this->get('/private-console/applications/'.$application->public_id.'/edit')
            ->assertOk()
            ->assertSee('验证数据')
            ->assertSee('客户端返回数组 a[2]')
            ->assertSee('通信认证 Key')
            ->assertSee('BSPHP_MUTUALKEY')
            ->assertSee('/AppEn.php?appid=10000001&amp;md5=', false)
            ->assertSee('现代混合加密')
            ->assertSee('10000001-软件配置')
            ->assertSee('role="tablist"', false)
            ->assertSee('data-config-tab="basic"', false)
            ->assertSee('data-config-tab="communication"', false)
            ->assertSee('data-config-tab="limits"', false)
            ->assertDontSee('data-config-tab="risk"', false)
            ->assertDontSee('data-risk-panel', false)
            ->assertSee('data-workspace-bootstrap-url=', false)
            ->assertSee('data-workspace-bootstrap-title="10000001-软件配置"', false)
            ->assertDontSee('封控策略')
            ->assertDontSee('IP 频率和阻断规则')
            ->assertDontSee('/private-console/risk-control?application='.$application->public_id, false)
            ->assertSee('基本配置')
            ->assertSee('软件开关')
            ->assertSee('关闭提示')
            ->assertSee('通信安全')
            ->assertSee('限开控制')
            ->assertDontSee('是否绑定模式')
            ->assertDontSee('name="bind_device_enabled"', false)
            ->assertDontSee('name="login_api_name"', false);

        $this->put('/private-console/applications/'.$application->public_id, [
            'name' => '游戏 iOS 正式版',
            'description' => '已更新',
            'status' => 'active',
            'closed_message' => '系统维护，请稍后再试。',
            'verification_data' => "0x100000\nworld-server=gwrold.example",
            'encryption_mode' => 'hybrid_rsa_aes_hmac',
            'max_bound_devices' => -1,
            'max_concurrent_sessions' => -1,
            'version_name' => '1.1.0',
            'legacy_enabled' => '1',
            'login_api_name' => 'login.lg',
            'max_clock_skew_seconds' => 600,
        ])->assertRedirect();

        $application->refresh();
        $this->assertSame('游戏 iOS 正式版', $application->name);
        $this->assertSame("0x100000\nworld-server=gwrold.example", $application->verificationData());
        $this->assertSame('constant', $application->payload_mode);
        $this->assertTrue($application->bind_device_enabled);
        $this->assertSame(-1, $application->max_bound_devices);
        $this->assertSame(-1, $application->max_concurrent_sessions);
        $this->assertSame('login.ic', $application->legacyConfig()->sole()->login_api_name);

        $this->post('/private-console/applications/'.$application->public_id.'/toggle')
            ->assertRedirect();
        $this->assertSame('suspended', $application->fresh()->status);

        $this->post('/private-console/applications', [
            'code' => '10000002',
            'name' => '账户模式软件',
            'mode' => 'account_recharge',
        ])->assertRedirect();

        $accountApplication = Application::query()->where('code', '10000002')->sole();
        $accountLegacy = $accountApplication->legacyConfig()->sole();
        $this->assertSame('login.lg', $accountLegacy->login_api_name);
        $this->assertSame('user', $accountLegacy->credential_param);
        $this->assertSame('pwd', $accountLegacy->credential_secret_param);
        $this->get('/private-console/applications/'.$accountApplication->public_id.'/edit')
            ->assertOk()
            ->assertSee('login.lg')
            ->assertSee('首次注册赠送')
            ->assertSee('邀请赠送时长')
            ->assertDontSee('是否绑定模式')
            ->assertSee('转绑定扣除时长');
        $this->get('/private-console/applications/'.$accountApplication->public_id.'/users')
            ->assertOk()
            ->assertSee('账号登录模式')
            ->assertSee('批量操作')
            ->assertSee('data-user-bulk-dialog', false)
            ->assertSee('应用账号批量维护')
            ->assertSee('冻结账号')
            ->assertDontSee('充值卡用户')
            ->assertSee('操作')
            ->assertDontSee('用户分组');

        $account = ApplicationAccount::query()->create([
            'public_id' => (string) Str::ulid(),
            'application_id' => $accountApplication->getKey(),
            'username' => 'test01', 'password_hash' => bcrypt('secret123'), 'status' => 'active',
            'bound_device_ciphertext' => 'IOS-FULL-BINDING-81FA', 'bound_device_mask' => 'IOS-••••81FA',
        ]);
        $this->get('/private-console/applications/'.$accountApplication->public_id.'/users')
            ->assertOk()->assertSee('IOS-FULL-BINDING-81FA')->assertDontSee('IOS-••••81FA')->assertSee('详情');
        $workspaceUrl = '/private-console/applications/'.$accountApplication->public_id.'/users?keyword=test01&sort=latest';
        $workspaceUpdateResponse = $this->from('/private-console/applications')
            ->withHeader('X-Workspace-Url', url($workspaceUrl))
            ->put('/private-console/applications/'.$accountApplication->public_id.'/account-users/'.$account->public_id, [
                'expires_at' => '2027-07-19 12:00:00', 'bound_device' => 'IOS-UPDATED-FULL-BINDING',
                'account_concurrency_limit' => 2, 'machine_concurrency_limit' => 3, 'note' => '后台备注',
            ]);
        $this->withoutHeader('X-Workspace-Url');
        $workspaceUpdateResponse->assertRedirect($workspaceUrl);
        $this->assertSame('IOS-UPDATED-FULL-BINDING', $account->fresh()->bound_device_ciphertext);

        $this->get('/private-console/application-users/maintenance')
            ->assertOk()
            ->assertSee('应用账号批量维护')
            ->assertSee('A.ID批量处理')
            ->assertSee('B.筛选选处理')
            ->assertSee('添加时间N(秒/点)')
            ->assertSee('删除(谨慎操作)');
        $this->from('/private-console/application-users/maintenance')
            ->post('/private-console/application-users/maintenance', [
                'selection_mode' => 'ids',
                'id_list' => (string) $account->getKey(),
                'application_id' => $accountApplication->getKey(),
                'scope' => 'all',
                'action' => 'freeze',
                'confirmed' => '1',
            ])->assertRedirect('/private-console/application-users/maintenance');
        $this->assertSame('frozen', $account->fresh()->status);

        $this->delete('/private-console/applications/'.$accountApplication->public_id, [
            'delete_application' => '1',
            'delete_users' => '1',
            'delete_cards' => '1',
            'delete_card_types' => '1',
        ])->assertRedirect('/private-console/applications');
        $this->assertSoftDeleted('applications', ['id' => $accountApplication->getKey()]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'application.deleted',
            'target_id' => $accountApplication->public_id,
        ]);

        $this->assertTrue(Card::query()->where('application_id', $application->getKey())->exists());
        $this->delete('/private-console/applications/'.$application->public_id, [
            'delete_cards' => '1',
        ])->assertRedirect('/private-console/applications');
        $this->assertNotSoftDeleted('applications', ['id' => $application->getKey()]);
        $this->assertDatabaseMissing('cards', ['application_id' => $application->getKey()]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'application.related_data_deleted',
            'target_id' => $application->public_id,
        ]);

    }

    private function administrator(): Administrator
    {
        $administrator = Administrator::query()->create([
            'username' => 'app-admin',
            'password' => 'primary-secret',
            'role' => 'super_admin',
            'status' => 'active',
        ]);
        $administrator->roles()->attach(Role::query()->where('code', 'super_admin')->sole());

        return $administrator;
    }
}
