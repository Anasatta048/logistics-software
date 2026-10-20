<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A load can name a saved driver or just type one. Not every haulage runs on a
 * driver we have a record for, and the load should not be blocked because of
 * it — so the name is kept alongside driver_id rather than instead of it.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('loads', function (Blueprint $table) {
            $table->string('driver_name')->nullable()->after('driver_id');
        });
    }

    public function down(): void
    {
        Schema::table('loads', function (Blueprint $table) {
            $table->dropColumn('driver_name');
        });
    }
};
