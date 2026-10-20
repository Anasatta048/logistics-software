<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An invoice is what a company owes; each payment received against it is a
 * company payment. The invoice is Pending until money arrives, then Partially
 * Settled or Fully Settled (derived from the payments, never typed).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('company_payments', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('company_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invoice_id');
        });
    }
};
