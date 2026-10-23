<?php

namespace App\Http\Controllers;

use App\Cards\CardActivationException;
use App\Cards\CardTimerService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * 计时卡自助页：用户只输入卡号，自行冻结或恢复计时。
 * 带卡密码的卡种不支持网页操作（按卡密码错误处理），只能走客户端接口。
 * 冻结后代理联动在下一次同步时停止访问，恢复后自动放行。
 */
final class PublicCardTimerController extends Controller
{
    /** 这些错误可以原样提示用户；其余统一提示为卡号错误，避免泄露卡状态细节。 */
    private const VISIBLE_ERRORS = [
        'TIMER_NOT_SUPPORTED', 'TIMER_NOT_ACTIVATED', 'TIMER_ALREADY_PAUSED', 'TIMER_PAUSE_LIMIT',
        'CARD_EXPIRED', 'CARD_FROZEN', 'USER_FROZEN', 'APP_DISABLED',
    ];

    public function show(): View
    {
        return view('public.card-timer', ['credential' => '', 'timer' => null, 'notice' => null, 'error' => null]);
    }

    public function run(Request $request, CardTimerService $timers): View
    {
        $validated = $request->validate([
            'credential' => ['required', 'string', 'min:6', 'max:64'],
            'action' => ['required', Rule::in(['status', 'pause', 'resume'])],
        ], [
            'credential.required' => '请输入卡号。',
            'credential.min' => '卡号长度至少为 6 位。',
        ]);
        $code = trim((string) $validated['credential']);
        $secret = '';
        $action = $validated['action'];

        $timer = null;
        $notice = null;
        $error = null;
        try {
            $application = $timers->applicationForCredential($code)
                ?? throw new CardActivationException('INVALID_CARD', '卡密不存在。', 404);
            $timer = match ($action) {
                'pause' => $timers->pause($application, $code, $secret, null)['timer'],
                'resume' => $timers->resume($application, $code, $secret, null)['timer'],
                default => $timers->status($application, $code, $secret, null)['timer'],
            };
            $timer['application_name'] = $application->name;
            $notice = match ($action) {
                'pause' => '已冻结，不扣时长',
                'resume' => '已恢复计时',
                default => null,
            };
        } catch (CardActivationException $exception) {
            $error = in_array($exception->errorCode, self::VISIBLE_ERRORS, true)
                ? $exception->getMessage()
                : '卡号不存在，请核对后重试。';
        }

        return view('public.card-timer', [
            'credential' => $code,
            'timer' => $timer,
            'notice' => $notice,
            'error' => $error,
        ]);
    }
}
