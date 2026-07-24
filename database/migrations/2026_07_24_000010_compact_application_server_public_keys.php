<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('applications')
            ->whereNotNull('server_public_key')
            ->orderBy('id')
            ->eachById(function (object $application): void {
                $compact = preg_replace(
                    '/(?:-----BEGIN RSA PUBLIC KEY-----|-----END RSA PUBLIC KEY-----|\s+)/',
                    '',
                    (string) $application->server_public_key,
                );

                DB::table('applications')
                    ->where('id', $application->id)
                    ->update(['server_public_key' => $compact]);
            });
    }

    public function down(): void
    {
        DB::table('applications')
            ->whereNotNull('server_public_key')
            ->orderBy('id')
            ->eachById(function (object $application): void {
                $body = preg_replace('/\s+/', '', (string) $application->server_public_key) ?? '';
                $pem = "-----BEGIN RSA PUBLIC KEY-----\n"
                    .chunk_split($body, 64, "\n")
                    ."-----END RSA PUBLIC KEY-----\n";

                DB::table('applications')
                    ->where('id', $application->id)
                    ->update(['server_public_key' => $pem]);
            });
    }
};
