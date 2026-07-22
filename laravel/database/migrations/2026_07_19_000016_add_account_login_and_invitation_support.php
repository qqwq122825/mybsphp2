<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->unsignedInteger('invitation_bonus_value')->default(0)->after('max_concurrent_sessions');
            $table->string('invitation_bonus_unit', 16)->default('day')->after('invitation_bonus_value');
        });

        Schema::table('application_accounts', function (Blueprint $table): void {
            $table->string('invitation_code', 24)->nullable()->after('username');
            $table->foreignId('invited_by_account_id')->nullable()->after('invitation_code')
                ->constrained('application_accounts')->nullOnDelete();
            $table->smallInteger('account_concurrency_limit')->default(0)->after('failed_attempt_count');
            $table->smallInteger('machine_concurrency_limit')->default(0)->after('account_concurrency_limit');
            $table->string('note', 500)->nullable()->after('machine_concurrency_limit');
            $table->unique(['application_id', 'invitation_code']);
        });

        Schema::table('cards', function (Blueprint $table): void {
            $table->foreignId('redeemed_by_account_id')->nullable()->after('application_id')
                ->constrained('application_accounts')->nullOnDelete();
            $table->smallInteger('account_concurrency_limit')->default(0)->after('user_note');
            $table->smallInteger('machine_concurrency_limit')->default(0)->after('account_concurrency_limit');
        });

        Schema::create('account_entitlement_transactions', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('application_account_id')->constrained('application_accounts')->cascadeOnDelete();
            $table->foreignId('card_id')->nullable()->constrained('cards')->nullOnDelete();
            $table->string('type', 32);
            $table->unsignedBigInteger('seconds')->default(0);
            $table->bigInteger('balance_cents')->default(0);
            $table->unsignedBigInteger('points')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['card_id', 'type']);
            $table->index(['application_account_id', 'created_at'], 'acct_tx_account_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_entitlement_transactions');
        Schema::table('cards', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('redeemed_by_account_id');
            $table->dropColumn(['account_concurrency_limit', 'machine_concurrency_limit']);
        });
        Schema::table('application_accounts', function (Blueprint $table): void {
            $table->dropUnique(['application_id', 'invitation_code']);
            $table->dropConstrainedForeignId('invited_by_account_id');
            $table->dropColumn(['invitation_code', 'account_concurrency_limit', 'machine_concurrency_limit', 'note']);
        });
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropColumn(['invitation_bonus_value', 'invitation_bonus_unit']);
        });
    }
};
