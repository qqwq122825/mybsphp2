<?php

namespace Database\Seeders;

use App\Auth\SystemAccessSynchronizer;
use Illuminate\Database\Seeder;

final class SystemAccessSeeder extends Seeder
{
    public function run(): void
    {
        app(SystemAccessSynchronizer::class)->sync();
    }
}
