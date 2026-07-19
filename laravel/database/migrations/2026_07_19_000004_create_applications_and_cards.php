<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('code', 40)->unique();
            $table->string('name', 120);
            $table->string('description', 500)->nullable();
            $table->string('mode', 32)->index();
            $table->string('entitlement_kind', 24);
            $table->string('status', 24)->default('draft')->index();
            $table->string('payload_mode', 24)->default('constant');
            $table->text('payload_value_ciphertext')->nullable();
            $table->string('client_secret_hash', 255);
            $table->boolean('bind_device_enabled')->default(true);
            $table->unsignedSmallInteger('max_bound_devices')->default(1);
            $table->unsignedSmallInteger('max_concurrent_sessions')->default(1);
            $table->string('version_name', 60)->nullable();
            $table->text('announcement')->nullable();
            $table->foreignId('created_by_administrator_id')
                ->nullable()
                ->constrained('administrators')
                ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'mode']);
        });

        Schema::create('application_legacy_configs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')
                ->unique()
                ->constrained('applications')
                ->cascadeOnDelete();
            $table->string('appid_param', 64)->default('appid');
            $table->string('signature_param', 64)->default('md5');
            $table->string('payload_param', 64)->default('parameter');
            $table->string('api_param', 64)->default('api');
            $table->string('credential_param', 64)->default('icid');
            $table->string('credential_secret_param', 64)->default('icpwd');
            $table->string('device_param', 64)->default('key');
            $table->string('appsafecode_param', 64)->default('appsafecode');
            $table->string('login_api_name', 64)->default('login.ic');
            $table->unsignedInteger('max_clock_skew_seconds')->default(300);
            $table->boolean('legacy_enabled')->default(true);
            $table->json('extra_config')->nullable();
            $table->timestamps();
        });

        Schema::create('card_types', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('application_id')
                ->constrained('applications')
                ->restrictOnDelete();
            $table->string('name', 100);
            $table->string('code', 40);
            $table->string('purpose', 32);
            $table->string('benefit_kind', 24);
            $table->unsignedBigInteger('duration_seconds')->nullable();
            $table->unsignedBigInteger('points_amount')->nullable();
            $table->dateTime('fixed_expires_at')->nullable();
            $table->unsignedBigInteger('face_price_cents')->default(0);
            $table->unsignedBigInteger('default_agent_price_cents')->default(0);
            $table->string('code_prefix', 16)->default('BS');
            $table->unsignedTinyInteger('code_length')->default(20);
            $table->unsignedTinyInteger('secret_length')->default(8);
            $table->string('alphabet', 64)->default('ABCDEFGHJKLMNPQRSTUVWXYZ23456789');
            $table->string('status', 24)->default('active')->index();
            $table->unsignedSmallInteger('max_activations')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['application_id', 'code']);
        });

        Schema::create('card_batches', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('card_type_id')
                ->constrained('card_types')
                ->restrictOnDelete();
            $table->string('batch_no', 48)->unique();
            $table->string('source', 24)->default('generated');
            $table->unsignedInteger('planned_quantity');
            $table->unsignedInteger('actual_quantity')->default(0);
            $table->string('status', 24)->default('draft')->index();
            $table->foreignId('created_by_administrator_id')
                ->nullable()
                ->constrained('administrators')
                ->nullOnDelete();
            $table->dateTime('generated_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cards', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('batch_id')
                ->constrained('card_batches')
                ->restrictOnDelete();
            $table->foreignId('card_type_id')
                ->constrained('card_types')
                ->restrictOnDelete();
            $table->foreignId('application_id')
                ->constrained('applications')
                ->restrictOnDelete();
            $table->char('code_lookup_hash', 64)->unique();
            $table->text('code_ciphertext');
            $table->char('code_last4', 4);
            $table->string('secret_hash', 255)->nullable();
            $table->text('secret_ciphertext')->nullable();
            $table->char('secret_last4', 4)->nullable();
            $table->string('status', 24)->default('inventory')->index();
            $table->dateTime('issued_at')->nullable();
            $table->dateTime('activated_at')->nullable();
            $table->dateTime('consumed_at')->nullable();
            $table->dateTime('expires_at')->nullable()->index();
            $table->dateTime('frozen_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->text('bound_device_ciphertext')->nullable();
            $table->string('bound_device_mask', 80)->nullable();
            $table->unsignedSmallInteger('activation_count')->default(0);
            $table->unsignedInteger('failed_attempt_count')->default(0);
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->index(['application_id', 'status', 'created_at']);
            $table->index(['card_type_id', 'status']);
        });

        Schema::create('manual_issues', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('issue_no', 48)->unique();
            $table->foreignId('application_id')
                ->constrained('applications')
                ->restrictOnDelete();
            $table->foreignId('operator_administrator_id')
                ->nullable()
                ->constrained('administrators')
                ->nullOnDelete();
            $table->string('target_type', 24)->default('offline');
            $table->text('offline_recipient_ciphertext')->nullable();
            $table->string('recipient_display_mask', 120)->nullable();
            $table->string('status', 24)->default('committed')->index();
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('total_cost_cents')->default(0);
            $table->string('idempotency_key', 80);
            $table->string('note', 500)->nullable();
            $table->dateTime('committed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['operator_administrator_id', 'idempotency_key']);
        });

        Schema::create('manual_issue_items', function (Blueprint $table): void {
            $table->foreignId('manual_issue_id')
                ->constrained('manual_issues')
                ->cascadeOnDelete();
            $table->foreignId('card_id')
                ->unique()
                ->constrained('cards')
                ->restrictOnDelete();
            $table->unsignedBigInteger('unit_cost_cents')->default(0);
            $table->dateTime('delivered_at');
            $table->dateTime('exported_at')->nullable();
            $table->string('delivery_note', 255)->nullable();
            $table->timestamps();

            $table->primary(['manual_issue_id', 'card_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_issue_items');
        Schema::dropIfExists('manual_issues');
        Schema::dropIfExists('cards');
        Schema::dropIfExists('card_batches');
        Schema::dropIfExists('card_types');
        Schema::dropIfExists('application_legacy_configs');
        Schema::dropIfExists('applications');
    }
};
