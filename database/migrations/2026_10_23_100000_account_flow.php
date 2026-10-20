<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Money-flow groundwork: ledger rows get a business date (`occurred_on`, the
 * date of the expense / receipt / payout, not the day it was keyed) and money
 * can be moved between the company's own accounts (account_transfers).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('account_transactions', function (Blueprint $table): void {
            $table->date('occurred_on')->nullable()->after('running_balance');
        });

        DB::table('account_transactions')->whereNull('occurred_on')->update(['occurred_on' => DB::raw('DATE(created_at)')]);

        Schema::table('account_transactions', function (Blueprint $table): void {
            $table->index(['tenant_id', 'account_id', 'occurred_on'], 'acct_tx_tenant_account_date_idx');
            $table->index(['tenant_id', 'occurred_on'], 'acct_tx_tenant_date_idx');
        });

        Schema::create('account_transfers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->foreignId('from_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('to_account_id')->constrained('accounts')->restrictOnDelete();
            $table->decimal('amount', 14, 2);
            // Bank / transfer charge, taken from the source account on top of the amount.
            $table->decimal('fee', 14, 2)->default(0);
            $table->date('transfer_date');
            $table->string('reference', 60)->nullable();
            $table->string('narration', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'transfer_date'], 'transfers_tenant_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_transfers');
        Schema::table('account_transactions', function (Blueprint $table): void {
            $table->dropIndex('acct_tx_tenant_account_date_idx');
            $table->dropIndex('acct_tx_tenant_date_idx');
            $table->dropColumn('occurred_on');
        });
    }
};
