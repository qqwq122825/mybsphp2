<?php

namespace Tests\Fixtures;

final class UserFixture
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function attributes(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Fixture User',
            'email' => 'fixture.user@example.test',
        ], $overrides);
    }
}
