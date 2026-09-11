<?php

namespace Tests\Feature;

use Tests\Support\AdminTablePreview;
use Tests\TestCase;

class AdminTablePresentationTest extends TestCase
{
    public function test_software_pages_keep_backend_fields_and_mount_component_views(): void
    {
        $list = AdminTablePreview::render('software');
        $this->assertStringContainsString('data-element-software', $list);
        $this->assertStringContainsString('name="delete_application"', $list);
        $this->assertStringContainsString('name="delete_card_types"', $list);
        $this->assertStringContainsString('data-workspace-title="王者荣耀巨魔 · 软件配置"', $list);
        $this->assertStringContainsString('还没有软件', AdminTablePreview::render('software-empty'));
        foreach (['software-config', 'software-account-config', 'software-create'] as $page) {
            $html = AdminTablePreview::render($page);
            $this->assertStringContainsString('data-element-config', $html);
            $this->assertStringContainsString('data-form-errors=', $html);
            $this->assertStringContainsString('name="name"', $html);
            $this->assertStringContainsString('name="_token"', $html);
            if ($page !== 'software-create') {
                $this->assertStringContainsString('name="_method" value="PUT"', $html);
                $this->assertStringContainsString('name="encryption_mode" value="hybrid_rsa_aes_hmac"', $html);
                $this->assertStringContainsString('name="rebind_deduction_value"', $html);
                $this->assertStringContainsString('name="verification_data"', $html);
                $this->assertStringContainsString('name="registration_bonus_value"', $html);
            }
        }
        foreach (['software-users', 'software-account-users'] as $page) {
            $html = AdminTablePreview::render($page);
            $this->assertStringContainsString('data-element-list data-list-kind="users"', $html);
            $this->assertStringContainsString('data-user-detail-form', $html);
            $this->assertStringContainsString('name="expires_at"', $html);
            $this->assertStringContainsString('name="account_concurrency_limit"', $html);
            $this->assertStringContainsString('name="id_list"', $html);
            $this->assertStringContainsString('value="set_expiry"', $html);
            $this->assertStringContainsString('data-per-page="30"', $html);
        }
    }

    public function test_risk_center_uses_shared_actions_and_handles_long_identifiers(): void
    {
        $html = AdminTablePreview::render('center');
        $this->assertStringContainsString('data-element-list data-list-kind="risk"', $html);
        $this->assertStringContainsString('admin-action-column', $html);
        $this->assertStringContainsString('admin-table-action--primary', $html);
        $this->assertStringContainsString('name="block_keys[]"', $html);
        $this->assertStringContainsString('form="risk-center-release-form"', $html);
        $this->assertStringContainsString('3CFDD33C-C703-42F0-9E00-3B36090270EC', $html);
        $this->assertStringContainsString('2001:db8:2000:9872:f425:3ba4:a953:4363', $html);
        $this->assertStringContainsString('aria-pressed="false"', $html);
        $this->assertStringContainsString('class="risk-center-related" data-cell-ellipsis', $html);
        $this->assertStringContainsString('当前没有封控记录。', AdminTablePreview::render('empty'));
    }

    public function test_log_variants_share_actions_without_changing_submission_values(): void
    {
        foreach (['blocklist', 'simple', 'detailed', 'protection'] as $page) {
            $html = AdminTablePreview::render($page);
            $this->assertStringContainsString('data-element-list data-list-kind="logs"', $html);
            $this->assertStringContainsString('admin-table-action--danger', $html);
            $this->assertStringContainsString('admin-table-action--neutral', $html);
            $this->assertStringContainsString('value="delete_older_7"', $html);
            $this->assertStringContainsString('value="delete_older_50"', $html);
            $this->assertStringContainsString('value="clear"', $html);
            $this->assertStringNotContainsString('维护删除最近', $html);
            $this->assertStringContainsString('data-table-export', $html);
            if ($page !== 'blocklist') {
                $this->assertStringContainsString('admin-action-column', $html);
                if ($page !== 'protection') {
                    $this->assertStringContainsString('is-muted', $html);
                }
                $this->assertStringNotContainsString('name="ip"', $html);
            }
            if ($page !== 'protection') {
                $this->assertStringContainsString('data-client-trace-detail', $html);
                $this->assertStringContainsString('admin-detail-column', $html);
            }
        }
    }

    public function test_administrator_list_reuses_the_same_tools_and_action_style(): void
    {
        $html = AdminTablePreview::render('administrators');
        $this->assertStringContainsString('admin-table-action--primary', $html);
        $this->assertStringContainsString('aria-label="表格工具"', $html);
        $this->assertStringContainsString('data-admin-manage', $html);
    }
}
