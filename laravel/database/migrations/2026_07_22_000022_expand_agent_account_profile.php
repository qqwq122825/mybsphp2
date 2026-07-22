<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('administrators', function (Blueprint $table): void {
            $table->unsignedSmallInteger('agent_discount_basis_points')->default(10000)->after('agent_level');
            $table->string('agent_note', 500)->nullable()->after('agent_discount_basis_points');
            $table->string('contact_qq', 32)->nullable()->after('agent_note');
            $table->string('contact_email')->nullable()->after('contact_qq');
            $table->string('contact_phone', 32)->nullable()->after('contact_email');
            $table->boolean('agent_all_applications')->default(true)->after('contact_phone');
        });

        Schema::create('agent_application_access', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('agent_id')->constrained('administrators')->cascadeOnDelete();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['agent_id', 'application_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_application_access');
        Schema::table('administrators', function (Blueprint $table): void {
            $table->dropColumn([
                'agent_discount_basis_points',
                'agent_note',
                'contact_qq',
                'contact_email',
                'contact_phone',
                'agent_all_applications',
            ]);
        });
    }
};
