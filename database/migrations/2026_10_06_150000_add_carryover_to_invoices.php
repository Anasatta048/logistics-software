<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Carry-over between a company's invoices, always the dispatcher's choice:
 * a new invoice may take on the earlier unpaid balance (previous_balance) and/or
 * deduct credit from earlier overpayments (credit_applied). The source invoices
 * remember it so nothing is counted twice.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('previous_balance', 12, 2)->default(0)->after('total');
            $table->decimal('credit_applied', 12, 2)->default(0)->after('previous_balance');
            // On a source invoice: how much of its overpayment later invoices used.
            $table->decimal('credit_used', 12, 2)->default(0)->after('credit_applied');
            // On a source invoice: the newer invoice its unpaid balance moved to.
            $table->foreignId('carried_to_id')->nullable()->after('credit_used')->constrained('invoices')->nullOnDelete();
            // On the new invoice: where its carry-over came from (for undoing on delete).
            $table->json('carryover')->nullable()->after('carried_to_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('carried_to_id');
            $table->dropColumn(['previous_balance', 'credit_applied', 'credit_used', 'carryover']);
        });
    }
};
