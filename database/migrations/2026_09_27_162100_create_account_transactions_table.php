<?php

use App\Enums\TransactionSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('type');
            $table->decimal('amount', 14, 2);
            // Balance of this account immediately after this row, so the ledger
            // can be read top to bottom without replaying the whole history.
            $table->decimal('running_balance', 14, 2);
            // ('expense', 12) / ('journal_voucher', 3) / ('manual', null) — a
            // plain pair, not a morph, because a manual entry has no model.
            $table->string('source_type')->default(TransactionSource::Manual->value);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('description');
            $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index(['account_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_transactions');
    }
};
