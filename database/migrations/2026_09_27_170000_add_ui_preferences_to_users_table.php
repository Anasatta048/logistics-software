<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * UI_UX_DESIGN_PLAN.md Section 2.2 (Sidebar/Topbar preference) and Section 6
 * (theme mode + notification preferences). Kept as plain columns on `users`
 * rather than a separate preferences table — a handful of small, rarely
 * queried settings don't earn a new table.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nav_position')->default('sidebar')->after('commission_percentage');
            $table->string('theme_mode')->default('system')->after('nav_position');
            $table->json('notification_preferences')->nullable()->after('theme_mode');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nav_position', 'theme_mode', 'notification_preferences']);
        });
    }
};
