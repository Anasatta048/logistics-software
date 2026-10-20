<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A line reads "<origin address> TO <destination address>", which easily runs
 * past 255 characters and made the invoice insert fail on MySQL.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->text('description')->change();
        });
    }

    public function down(): void
    {
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->string('description')->change();
        });
    }
};
