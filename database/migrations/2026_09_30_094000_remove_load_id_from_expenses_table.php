<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Expenses are company overhead in PKR; loads are USD revenue. Netting one
 * against the other was never arithmetically meaningful, so the link goes
 * rather than sitting there unused.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['load_id']);
            $table->dropColumn('load_id');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('load_id')->nullable()->after('account_id')->constrained()->nullOnDelete();
        });
    }
};
