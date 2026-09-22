<?php

namespace App\Proxy;

use App\Models\Application;
use App\Models\ApplicationAccount;
use App\Models\Card;
use App\Models\ProxyCredential;
use App\Models\ProxyLink;

final class ProxyLinkService
{
    public const PROTOCOL = 'dfmproxy-link-v1';

    public const MAX_USERS = 10000;

    public function ensure(Application $application): ProxyLink
    {
        return ProxyLink::query()->firstOrCreate(['application_id' => $application->id], [
            'path' => 'lk_'.bin2hex(random_bytes(16)),
            'token_ciphertext' => bin2hex(random_bytes(32)),
        ]);
    }

    /**
     * A bounded, complete snapshot. Missing subjects revoke access on the node.
     *
     * @return list<array<string, mixed>>
     */
    public function users(Application $application, ProxyLink $link): array
    {
        $subjects = $application->mode === 'card_login'
            ? $application->cards()->with('cardType')->whereNotNull('activated_at')->orderBy('id')->limit(self::MAX_USERS + 1)->get()
            : $application->accounts()->orderBy('id')->limit(self::MAX_USERS + 1)->get();
        abort_if($subjects->count() > self::MAX_USERS, 413, '用户数量超出完整同步上限');
        $credentials = $application->mode === 'card_login' ? collect() : ProxyCredential::query()
            ->where('application_id', $application->id)->get()->keyBy(fn (ProxyCredential $c) => $c->subject_type.':'.$c->subject_id);

        return $subjects->map(function (Card|ApplicationAccount $subject) use ($application, $link, $credentials): array {
            $type = $subject instanceof Card ? 'card' : 'account';
            $identity = $type.':'.$subject->public_id;
            if ($subject instanceof Card) {
                // Use the existing encrypted card code; never duplicate it into a plaintext username column.
                $proxyCode = (string) $subject->code_ciphertext;
            } else {
                $credential = $credentials->get($identity) ?? ProxyCredential::query()->firstOrCreate([
                    'application_id' => $application->id,
                    'subject_type' => $type,
                    'subject_id' => $subject->public_id,
                ], [
                    'username' => 'p'.$application->code.'_'.bin2hex(random_bytes(8)),
                    'password_ciphertext' => bin2hex(random_bytes(16)),
                ]);
                // Account mode has no single reusable card. Its separate proxy code fills both SOCKS fields.
                $proxyCode = (string) $credential->password_ciphertext;
            }
            $state = $subject->status;
            if ($subject instanceof Card) {
                // Freezing a used *card* is distinct from freezing its software user.
                if (in_array($subject->user_status, ['frozen', 'revoked'], true)) {
                    $state = $subject->user_status;
                } elseif ($subject->revoked_at) {
                    $state = 'revoked';
                } elseif ($subject->cardType->status !== 'active') {
                    $state = 'disabled';
                } elseif (! in_array($subject->cardType->benefit_kind, ['duration', 'fixed_expiry'], true)) {
                    $state = 'unsupported';
                }
            }
            // Current time-based applications require an explicit expiry. Null is not infinity.
            if ($state === 'active' && (! $subject->expires_at || $subject->expires_at->lte(now()))) {
                $state = 'expired';
            }

            return [
                'subject_id' => $identity, 'username' => $proxyCode,
                'password' => $proxyCode,
                'label' => $subject instanceof Card ? '卡密尾号 '.$subject->code_last4 : $subject->username,
                'expires_at' => $subject->expires_at?->getTimestamp(),
                'status' => $state, 'perm' => $link->permission, 'allow_multi' => false,
            ];
        })->all();
    }
}
