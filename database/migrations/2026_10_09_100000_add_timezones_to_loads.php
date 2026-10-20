<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pickup and dropoff are stored as UTC instants; these remember which US zone
 * the dispatcher typed each one in, so editing a load shows the same wall-clock
 * time it was booked with. Null means the entry zone (every load before this).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('loads', function (Blueprint $table) {
            $table->string('pickup_timezone', 64)->nullable()->after('pickup_at');
            $table->string('dropoff_timezone', 64)->nullable()->after('dropoff_at');
        });
    }

    public function down(): void
    {
        Schema::table('loads', function (Blueprint $table) {
            $table->dropColumn(['pickup_timezone', 'dropoff_timezone']);
        });
    }
};