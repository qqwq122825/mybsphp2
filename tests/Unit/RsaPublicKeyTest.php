<?php

namespace Tests\Unit;

use App\Applications\ApplicationCredentialService;
use App\Applications\RsaPublicKey;
use PHPUnit\Framework\TestCase;

class RsaPublicKeyTest extends TestCase
{
    public function test_generated_public_key_is_compact_and_openssl_compatible(): void
    {
        $generated = (new ApplicationCredentialService)->generate();
        $compact = $generated['server_public_key'];

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/]+={0,2}$/', $compact);
        $this->assertStringNotContainsString("\n", $compact);
        $this->assertNotFalse(openssl_pkey_get_public(RsaPublicKey::pem($compact)));
    }

    public function test_compact_accepts_existing_pem_values(): void
    {
        $generated = (new ApplicationCredentialService)->generate();
        $compact = $generated['server_public_key'];

        $this->assertSame($compact, RsaPublicKey::compact(RsaPublicKey::pem($compact)));
    }
}
