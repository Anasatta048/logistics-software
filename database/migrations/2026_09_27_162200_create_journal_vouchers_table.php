<?php

use App\Enums\JournalVoucherStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('journal_vouchers', function (Blueprint $table) {
            $table->id();
            // Stamped from the primary key in a second write, like a load's
            // reference number — no sequence table for one number a day.
            $table->string('jv_number')->nullable()->unique();
            $table->date('date');
            $table->foreignId('debit_account_id')->constrained('accounts')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('credit_account_id')->constrained('accounts')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('amount', 14, 2);
            $table->text('narration');
            $table->string('attachment_path')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->string('status')->default(JournalVoucherStatus::Pending->value)->index();
            // What makes a cancelled load's money reversible (SRD 6.3 / 12).
            $table->foreignId('load_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_vouchers');
    }
};
