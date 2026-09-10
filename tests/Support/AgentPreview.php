<?php

namespace Tests\Support;

use App\Models\Administrator;
use App\Models\AgentTransaction;
use App\Models\Application;
use App\Models\Card;
use App\Models\CardType;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

/** Component acceptance fixtures. No database writes, purchases or refunds. */
final class AgentPreview
{
    public static function render(string $page, array $common, Request $request): string
    {
        $agent = (new Administrator)->forceFill(['id' => 2, 'username' => 'preview_agent', 'role' => 'agent', 'status' => 'active', 'agent_level' => 2, 'agent_balance_cents' => $page === 'agent-purchase-low' ? 500 : 20000]);
        $common = array_merge($common, ['agent' => $agent]);
        if (str_starts_with($page, 'agent-login')) {
            if ($page === 'agent-login-error') {
                $common['errors'] = (new ViewErrorBag)->put('default', new MessageBag(['username' => '用户名或密码不正确，请检查后重试。']));
            }

            return view('agent.login', $common + ['captchaEnabled' => false, 'languageEnabled' => false, 'captcha' => null])->render();
        }
        if ($page === 'agent-dashboard') {
            return view('agent.dashboard', $common + ['announcement' => '欢迎使用代理平台。请在制卡前核对卡种、数量和账户余额。', 'metrics' => ['balance' => 20000, 'total' => 128, 'activated' => 96, 'unused' => 32, 'frozen' => 4, 'today_created' => 20, 'today_activated' => 12, 'today_frozen' => 1, 'yesterday_activated' => 18]])->render();
        }
        $at = CarbonImmutable::parse('2026-09-10 17:08:35');
        $application = (new Application)->forceFill(['id' => 1004, 'public_id' => 'preview-agent-app', 'name' => '桌面客户端', 'status' => 'active', 'rebind_deduction_value' => 1, 'rebind_deduction_unit' => 'hour']);
        $types = collect(['体验卡', '月卡', '免费测试卡'])->map(function ($name, $i) use ($application) {
            $type = (new CardType)->forceFill(['id' => $i + 1, 'public_id' => 'preview-type-'.$i, 'name' => $name, 'duration_value' => $i === 1 ? 30 : 1, 'duration_unit' => 'day', 'default_agent_price_cents' => [1500, 7000, 0][$i]]);
            $type->setRelation('application', $application);

            return $type;
        });
        if (str_starts_with($page, 'agent-purchase')) {
            return view('agent.cards.purchase', $common + ['cardTypes' => $page === 'agent-purchase-empty' ? collect() : $types, 'agentPrices' => $types->mapWithKeys(fn ($type) => [$type->id => $type->default_agent_price_cents]), 'generatedCards' => $page === 'agent-purchase-result' ? [['code' => 'DEMO-GENERATED-CARD-0001', 'secret' => ''], ['code' => 'DEMO-GENERATED-CARD-0002', 'secret' => '']] : [], 'generatedMeta' => ['application' => '桌面客户端', 'card_type' => '体验卡', 'quantity' => 2]])->render();
        }
        if (str_starts_with($page, 'agent-finance')) {
            $transactions = collect();
            if ($page !== 'agent-finance-empty') {
                for ($i = 0; $i < 24; $i++) {
                    $transactions->push((new AgentTransaction)->forceFill(['id' => $i + 1, 'created_at' => $at->subHours($i), 'type' => ['admin_recharge', 'card_purchase', 'card_refund'][$i % 3], 'amount_cents' => [10000, -1500, 1500][$i % 3], 'balance_after_cents' => 20000, 'note' => $i === 0 ? '余额充值示例' : '本地组件验收数据']));
                }
            }

            return view('agent.finance', $common + ['transactions' => new LengthAwarePaginator($transactions, $transactions->count(), 20, 1, ['path' => $request->url()])])->render();
        }
        $cards = collect();
        if ($page !== 'agent-cards-empty') {
            for ($i = 0; $i < 24; $i++) {
                $card = (new Card)->forceFill(['id' => $i + 1, 'public_id' => 'preview-agent-card-'.$i, 'code_ciphertext' => 'DEMO-AGENT-CARD-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT), 'status' => $i % 3 === 0 ? 'issued' : 'active', 'activated_at' => $i % 3 === 0 ? null : $at->subDays(1), 'user_status' => 'active', 'card_is_frozen' => $i === 3, 'agent_starred' => $i === 0, 'created_at' => $at->subMinutes($i)]);
                $card->setRelation('application', $application);
                $card->setRelation('cardType', $types[$i % 3]);
                $cards->push($card);
            }
        }
        $modal = ['id' => 1, 'code' => 'DEMO-AGENT-CARD-0001', 'application' => '桌面客户端', 'type' => '体验卡', 'refund' => '15.00', 'deduction' => '每次扣除 1 小时'];

        return view('agent.cards.index', $common + ['cards' => new LengthAwarePaginator($cards, $cards->count(), 20, 1, ['path' => $request->url()]), 'deleteCard' => $page === 'agent-delete' ? $modal : null, 'unbindCard' => $page === 'agent-unbind' ? $modal : null])->render();
    }
}
