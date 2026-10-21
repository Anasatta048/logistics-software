<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::hasColumn('tenants', 'is_demo') || Schema::table('tenants', function (Blueprint $table) {
            // Throw-away sandbox workspaces handed to landing-page visitors.
            $table->boolean('is_demo')->default(false)->after('slug')->index();
        });

        // One row per demo started. Only a hash of the visitor's IP is kept, just enough
        // to limit how many demos one address can start. Not tied to the tenant by a
        // foreign key: the row must outlive the purged workspace to keep counting.
        Schema::hasTable('demo_sessions') || Schema::create('demo_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->char('ip_hash', 64)->index();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('ended_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_sessions');
        Schema::table('tenants', fn (Blueprint $table) => $table->dropColumn('is_demo'));
    }
};
