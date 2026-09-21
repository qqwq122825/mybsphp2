<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use LogicException;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'public_id',
        'actor_user_id',
        'actor_role_snapshot',
        'action',
        'target_type',
        'target_id',
        'application_id',
        'request_id',
        'ip',
        'user_agent_hash',
        'before_data',
        'after_data',
        'reason',
        'result',
    ];

    protected function casts(): array
    {
        return [
            'before_data' => 'array',
            'after_data' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }

    public function actorAdministrator(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'actor_user_id');
    }

    public function displaySummary(): string
    {
        $label = match ($this->action) {
            'admin.login' => '后台登录成功',
            'admin.logout' => '后台退出登录',
            'admin.login_failed' => '后台登录失败',
            'agent.login' => '代理登录成功',
            'agent.logout' => '代理退出登录',
            'agent.login_failed' => '代理登录失败',
            'security.blocked' => '系统已封禁该访问',
            'security.rate_limited' => '登录尝试过多',
            default => $this->action,
        };

        $username = trim((string) data_get($this->after_data, 'username', ''));
        $reason = trim((string) ($this->reason ?? ''));
        $details = [];
        if ($username !== '') {
            $details[] = '账号 '.$username;
        }
        $password = $this->revealAttemptedPassword();
        if ($password !== null && $password !== '') {
            $details[] = '密码 '.$password;
        }
        $secondary = $this->revealAttemptedSecondary();
        if ($secondary !== null && $secondary !== '') {
            $details[] = '二级密码 '.$secondary;
        }
        if ($reason !== '' && $reason !== $label) {
            $details[] = $reason;
        }

        return $details === [] ? $label : $label.'：'.implode('，', $details);
    }

    /**
     * @param  array<string, mixed>  $after
     * @return array<string, mixed>
     */
    public static function withAttemptedCredentials(array $after, string $password, string $secondary, string $reason): array
    {
        if (in_array($reason, ['账号不存在', '密码不正确'], true) && $password !== '') {
            $after['attempted_password_ciphertext'] = Crypt::encryptString($password);
        }
        if (
            $secondary !== ''
            && in_array($reason, ['账号不存在', '密码不正确', '二级密码不正确'], true)
        ) {
            $after['attempted_secondary_ciphertext'] = Crypt::encryptString($secondary);
        }

        return $after;
    }

    /**
     * @param  array<string, mixed>  $after
     * @return array<string, mixed>
     */
    public static function withAttemptedSecret(array $after, string $plain, string $field = 'password'): array
    {
        return $field === 'secondary_password'
            ? static::withAttemptedCredentials($after, '', $plain, '二级密码不正确')
            : static::withAttemptedCredentials($after, $plain, '', '密码不正确');
    }

    public function revealAttemptedPassword(): ?string
    {
        if ($this->action === 'admin.login_failed') {
            return null;
        }

        $cipher = data_get($this->after_data, 'attempted_password_ciphertext');
        if (is_string($cipher) && $cipher !== '') {
            return $this->decryptAttemptedSecret($cipher);
        }
        if (data_get($this->after_data, 'attempted_field') === 'secondary_password') {
            return null;
        }

        return $this->revealLegacyAttemptedSecret();
    }

    public function revealAttemptedSecondary(): ?string
    {
        if ($this->action === 'admin.login_failed') {
            return null;
        }

        $cipher = data_get($this->after_data, 'attempted_secondary_ciphertext');
        if (is_string($cipher) && $cipher !== '') {
            return $this->decryptAttemptedSecret($cipher);
        }
        if (data_get($this->after_data, 'attempted_field') === 'secondary_password') {
            return $this->revealLegacyAttemptedSecret();
        }

        return null;
    }

    public function revealAttemptedLoginSecret(): ?string
    {
        return $this->revealAttemptedPassword() ?? $this->revealAttemptedSecondary();
    }

    public function actorDisplayName(): string
    {
        $administrator = $this->actorAdministrator?->username;
        if (is_string($administrator) && $administrator !== '') {
            return $administrator;
        }
        $attempted = trim((string) data_get($this->after_data, 'username', ''));
        if ($attempted !== '') {
            return $attempted;
        }
        if (filled($this->actor_user_id)) {
            return (string) $this->actor_user_id;
        }

        return (string) ($this->actor_role_snapshot ?: '系统');
    }

    public function canBlockLoginIp(): bool
    {
        return in_array($this->action, ['admin.login_failed', 'agent.login_failed', 'security.rate_limited'], true)
            && is_string($this->ip)
            && $this->ip !== '';
    }

    public function cardMakingSummary(?CardBatch $batch = null): string
    {
        $quantity = (int) data_get($this->after_data, 'quantity', 0);
        $operatorId = $this->actor_user_id ?: '系统';
        $isAgent = $this->actor_role_snapshot === 'agent' || str_starts_with((string) $this->action, 'agent.');
        $operatorLabel = $isAgent ? '代理UID' : '后台管理员UID';
        $appName = trim((string) data_get($this->after_data, 'application_name', ''))
            ?: trim((string) ($batch?->cardType?->application?->name ?? ''));
        $cardTypeName = trim((string) data_get($this->after_data, 'card_type_name', ''))
            ?: trim((string) ($batch?->cardType?->name ?? ''));
        $appName = $appName !== '' ? $appName : '未知软件';
        $cardTypeName = $cardTypeName !== '' ? $cardTypeName : '未知卡类';
        $context = "软件 {$appName}，卡类 {$cardTypeName}";

        return match ($this->action) {
            'card_batch.generated' => "后台制卡成功，{$context}，后台管理员UID:{$operatorId}，{$quantity}张",
            'agent.cards_purchased' => "代理制卡成功，{$context}，代理UID:{$operatorId}，{$quantity}张",
            default => ($this->reason ?: '充值卡维护完成')."，{$context}，{$operatorLabel}:{$operatorId}",
        };
    }

    private function revealLegacyAttemptedSecret(): ?string
    {
        $cipher = data_get($this->after_data, 'attempted_secret_ciphertext');

        return is_string($cipher) && $cipher !== '' ? $this->decryptAttemptedSecret($cipher) : null;
    }

    private function decryptAttemptedSecret(string $cipher): ?string
    {
        try {
            return Crypt::decryptString($cipher);
        } catch (\Throwable) {
            return null;
        }
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Audit records are immutable.');
        });

        static::deleting(function (): never {
            throw new LogicException('Audit records are immutable.');
        });
    }
}
