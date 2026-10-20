<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One append-only USD ledger for every counterparty the business settles with:
 * companies (they owe Dreamelect commission) and dispatchers (Dreamelect owes
 * them commission). Rows are never edited or deleted — a correction is a new,
 * opposite row — so the table is the audit trail of when money was owed, when
 * it arrived or left, and when it was paid ahead of time.
 *
 * `amount` is signed by its effect on the balance, which always means "what the
 * party still has to settle": positive grows it, negative shrinks it. A balance
 * below zero is an advance.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('party_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->string('party_type', 32);
            $table->unsignedBigInteger('party_id');
            $table->string('entry_type', 40);
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_after', 14, 2);
            // The load an accrual (or its reversal) belongs to: lets open items
            // be settled oldest load first.
            $table->foreignId('load_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('source_type', 40)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('description');
            $table->date('occurred_on');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['party_type', 'party_id', 'id']);
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('commission_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatcher_id')->constrained('users')->restrictOnDelete();
            $table->decimal('usd_amount', 12, 2);
            $table->decimal('conversion_rate', 12, 4);
            $table->decimal('pkr_amount', 14, 2);
            $table->foreignId('payment_account_id')->constrained('accounts')->restrictOnDelete();
            $table->date('paid_at');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['dispatcher_id', 'paid_at']);
        });

        // Which commissions a payout covered at the time it was made. Whatever
        // is left over is an advance: paid, but not yet earned.
        Schema::create('commission_payout_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_payout_id')->constrained()->cascadeOnDelete();
            $table->foreignId('commission_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_payout_allocations');
        Schema::dropIfExists('commission_payouts');
        Schema::dropIfExists('party_ledger_entries');
    }
};
