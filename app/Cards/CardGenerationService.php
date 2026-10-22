<?php

namespace App\Cards;

use App\Models\Administrator;
use App\Models\Card;
use App\Models\CardBatch;
use App\Models\CardType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class CardGenerationService
{
    private const DEFAULT_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function __construct(
        private readonly CardCredentialHasher $hasher,
    ) {}

    /**
     * @param  array{pause_limit?: int, pause_max_seconds?: int}|null  $timer  计时卡选项；null 为普通卡
     * @return array{batch: CardBatch, credentials: list<array{code: string, secret: string}>}
     */
    public function generate(
        CardType $cardType,
        int $quantity,
        ?Administrator $administrator,
        ?string $note = null,
        string $saleFlag = 'normal',
        ?string $makerReference = null,
        ?array $timer = null,
    ): array {
        if ($timer !== null && $cardType->benefit_kind !== 'duration') {
            throw new InvalidArgumentException('只有时长卡类型可以制作计时卡。');
        }

        if ($quantity < 1 || $quantity > 500) {
            throw new InvalidArgumentException('单批制卡数量必须在 1 到 500 之间。');
        }

        return DB::transaction(function () use (
            $cardType,
            $quantity,
            $administrator,
            $note,
            $saleFlag,
            $makerReference,
            $timer,
        ): array {
            $batch = CardBatch::query()->create([
                'public_id' => (string) Str::ulid(),
                'card_type_id' => $cardType->getKey(),
                'batch_no' => 'B'.now()->format('YmdHis').strtoupper(Str::random(5)),
                'source' => 'generated',
                'sale_flag' => $saleFlag,
                'planned_quantity' => $quantity,
                'actual_quantity' => 0,
                'status' => 'generating',
                'created_by_administrator_id' => $administrator?->getKey(),
                'maker_reference' => $makerReference,
                'note' => $note,
                'is_timer' => $timer !== null,
            ]);

            $credentials = [];
            for ($index = 0; $index < $quantity; $index++) {
                [$code, $secret] = $this->uniqueCredential($cardType);

                Card::query()->create([
                    'public_id' => (string) Str::ulid(),
                    'batch_id' => $batch->getKey(),
                    'card_type_id' => $cardType->getKey(),
                    'application_id' => $cardType->application_id,
                    'code_lookup_hash' => $this->hasher->lookupHash($code),
                    'code_ciphertext' => $code,
                    'code_last4' => substr($code, -4),
                    'secret_hash' => Hash::make($secret),
                    'secret_ciphertext' => $secret,
                    'secret_last4' => substr($secret, -4),
                    'status' => 'inventory',
                    'is_timer' => $timer !== null,
                    'timer_pause_limit' => (int) ($timer['pause_limit'] ?? -1),
                    'timer_pause_max_seconds' => (int) ($timer['pause_max_seconds'] ?? 0),
                ]);

                $credentials[] = ['code' => $code, 'secret' => $secret];
            }

            $batch->forceFill([
                'actual_quantity' => $quantity,
                'status' => 'ready',
                'generated_at' => now(),
            ])->save();

            return ['batch' => $batch, 'credentials' => $credentials];
        }, 3);
    }

    /**
     * @return array{string, string}
     */
    private function uniqueCredential(CardType $cardType): array
    {
        $alphabetValue = $cardType->getAttribute('alphabet');
        $alphabet = is_string($alphabetValue) && $alphabetValue !== ''
            ? $alphabetValue
            : self::DEFAULT_ALPHABET;
        $prefix = (string) $cardType->code_prefix;
        $bodyLength = $cardType->code_length - strlen($prefix);
        $secretLength = max(0, (int) $cardType->secret_length);
        $secretAlphabetValue = $cardType->getAttribute('secret_alphabet');
        $secretAlphabet = is_string($secretAlphabetValue) && $secretAlphabetValue !== ''
            ? $secretAlphabetValue
            : self::DEFAULT_ALPHABET;

        if ($bodyLength < 8) {
            throw new InvalidArgumentException('卡号长度至少需要比开头字符多 8 位。');
        }

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $code = $prefix.$this->randomFromAlphabet($alphabet, $bodyLength);
            if (! Card::query()->where('code_lookup_hash', $this->hasher->lookupHash($code))->exists()) {
                return [
                    $code,
                    $secretLength > 0
                        ? $this->randomFromAlphabet($secretAlphabet, $secretLength)
                        : '',
                ];
            }
        }

        throw new InvalidArgumentException('生成唯一卡密失败，请重试。');
    }

    private function randomFromAlphabet(string $alphabet, int $length): string
    {
        $output = '';
        $lastIndex = strlen($alphabet) - 1;

        if ($lastIndex < 9) {
            throw new InvalidArgumentException('卡密字符集至少需要 10 个不同字符。');
        }

        for ($index = 0; $index < $length; $index++) {
            $output .= $alphabet[random_int(0, $lastIndex)];
        }

        return $output;
    }
}
