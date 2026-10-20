<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('trial');
            $table->string('billing_cycle', 10)->default('monthly');
            $table->timestamp('trial_ends_at')->nullable();
            // End of the paid period; null while on a free trial.
            $table->timestamp('subscription_ends_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->string('suspended_reason')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // The expiry sweep and the status filters read these together.
            $table->index(['status', 'trial_ends_at']);
            $table->index(['status', 'subscription_ends_at']);
        });

        Schema::table('tenant_requests', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable()->after('plan_id')->constrained()->nullOnDelete();
        });

        Schema::create('platform_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('action', 60);
            $table->string('description')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_audit_logs');
        Schema::table('tenant_requests', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tenant_id');
        });
        Schema::dropIfExists('tenants');
    }
};
