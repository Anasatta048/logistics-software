<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Receivables and payables become a running balance per counterparty instead of
 * a per-load `commission_settled` flag:
 *
 *   company: SUM(commission of Paid loads) - SUM(received) - SUM(advance)
 *   user:    SUM(accrued commission)       - SUM(paid USD)  - SUM(advance)
 *
 * The advance columns hold money received or handed over beyond what was owed at
 * the time, so the next receipt/payout eats it first instead of it being lost or
 * silently over-settled.
 *
 * `commission_settled` is dropped: the balance answers "what is left" without a
 * flag that can disagree with the money. company_payment_loads stays as the
 * audit trail of which loads a receipt covered.
 */
return new class () extends Migration {
    public function up(): void
    {
        // MySQL has no transactional DDL, so a run that dies half way leaves
        // some of this applied. Every step checks first so re-running finishes
        // the job instead of failing on a column that already exists.
        if (! Schema::hasColumn('company_payment_loads', 'applied_amount')) {
            Schema::table('company_payment_loads', function (Blueprint $table) {
                // How much of that load's commission this receipt actually covered.
                // A load can be part-paid by one receipt and finished by the next,
                // so a bare id pair cannot say what is left.
                $table->decimal('applied_amount', 12, 2)->default(0)->after('load_id');
            });
        }

        if (! Schema::hasColumn('companies', 'advance_balance')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->decimal('advance_balance', 12, 2)->default(0)->after('payment_currency');
            });
        }

        if (! Schema::hasColumn('users', 'commission_advance_usd')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('commission_advance_usd', 12, 2)->default(0)->after('commission_fixed_amount');
            });
        }

        if (! Schema::hasColumn('company_payments', 'advance_applied')) {
            Schema::table('company_payments', function (Blueprint $table) {
                // The part of amount_received that was beyond what was owed, so the
                // show/edit screens can explain an overpayment without re-deriving it.
                $table->decimal('advance_applied', 12, 2)->default(0)->after('amount_received');
            });
        }

        if (! Schema::hasColumn('commissions', 'paid_usd')) {
            Schema::table('commissions', function (Blueprint $table) {
                // Partial payouts: how much of the USD cut has actually been handed
                // over. pkr_amount keeps the last conversion for reference; the
                // ledger line per payout is what actually moves money.
                $table->decimal('paid_usd', 12, 2)->default(0)->after('commission_amount');
            });
        }

        if (Schema::hasColumn('loads', 'commission_settled')) {
            Schema::table('loads', function (Blueprint $table) {
                $table->dropColumn('commission_settled');
            });
        }

        // loads_company_settlement_idx is deliberately left in place. MySQL
        // truncates it to (company_id, status) once the column is gone, and it
        // is now the supporting index for loads_company_id_foreign, so dropping
        // it is refused (error 1553) and would buy nothing.
    }

    public function down(): void
    {
        Schema::table('loads', function (Blueprint $table) {
            $table->boolean('commission_settled')->default(false);
        });

        Schema::table('commissions', function (Blueprint $table) {
            $table->dropColumn('paid_usd');
        });

        Schema::table('company_payments', function (Blueprint $table) {
            $table->dropColumn('advance_applied');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('commission_advance_usd');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('advance_balance');
        });

        Schema::table('company_payment_loads', function (Blueprint $table) {
            $table->dropColumn('applied_amount');
        });
    }
};
