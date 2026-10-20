<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Phase 7: an expense is money out of a real account, so the account
            // is required — an expense that silently skipped the ledger would be
            // a balance nobody can explain later.
            $table->foreignId('account_id')->after('amount')
                ->constrained()->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_id');
        });
    }
};
