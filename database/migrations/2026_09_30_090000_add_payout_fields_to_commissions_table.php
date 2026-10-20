<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paying a dispatcher their cut. The commission is earned in USD (the company
 * charges in USD) but everybody is paid in PKR, so the rate used at payout time
 * has to survive on the row next to the amount it produced — same reasoning as
 * currency_conversions, and the two forms share the rate default.
 *
 * Columns stay nullable: an accrued commission has not been paid yet.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->decimal('conversion_rate', 12, 4)->nullable()->after('commission_amount');
            $table->decimal('pkr_amount', 14, 2)->nullable()->after('conversion_rate');
            $table->date('paid_at')->nullable()->after('pkr_amount');
            $table->foreignId('payment_account_id')->nullable()->after('paid_at')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('paid_by')->nullable()->after('payment_account_id')->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable()->after('paid_by');
        });
    }

    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->dropForeign(['payment_account_id']);
            $table->dropForeign(['paid_by']);
            $table->dropColumn([
                'conversion_rate', 'pkr_amount', 'paid_at',
                'payment_account_id', 'paid_by', 'notes',
            ]);
        });
    }
};
