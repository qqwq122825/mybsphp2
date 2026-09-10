<?php

namespace Tests\Feature;

use Tests\Support\AdminTablePreview;
use Tests\TestCase;

class AgentUiPresentationTest extends TestCase
{
    public function test_all_agent_pages_mount_the_component_library_with_original_server_forms(): void
    {
        foreach (['agent-dashboard', 'agent-cards', 'agent-purchase', 'agent-finance', 'agent-login', 'agent-purchase-empty', 'agent-cards-empty', 'agent-finance-empty', 'agent-delete', 'agent-unbind'] as $page) {
            $html = AdminTablePreview::render($page);
            $this->assertStringContainsString('data-element-agent', $html);
            $this->assertStringContainsString('data-form-errors=', $html);
            $this->assertStringContainsString('agent-ui-', $html);
            $this->assertStringContainsString('name="_token"', $html);
        }
        $this->assertStringNotContainsString('全国今天激活', AdminTablePreview::render('agent-dashboard'));
        $this->assertStringContainsString('暂无代理充值卡', AdminTablePreview::render('agent-cards-empty'));
        $this->assertStringContainsString('暂无余额流水', AdminTablePreview::render('agent-finance-empty'));
        $this->assertStringContainsString('data-agent-pagination', AdminTablePreview::render('agent-cards'));
        $this->assertStringContainsString('name="confirmed" value="1"', AdminTablePreview::render('agent-delete'));
        $this->assertStringContainsString('name="deduct_time" value="0"', AdminTablePreview::render('agent-unbind'));
        $this->assertStringContainsString('data-price="0"', AdminTablePreview::render('agent-purchase'));
        $this->assertStringContainsString("DEMO-GENERATED-CARD-0001\nDEMO-GENERATED-CARD-0002", AdminTablePreview::render('agent-purchase-result'));
    }
}
