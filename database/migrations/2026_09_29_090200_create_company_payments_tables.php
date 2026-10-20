<?php

use App\Enums\CompanyPaymentStatus;
use App\Enums\PaymentCurrency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('company_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('period_from');
            $table->date('period_to');
            // Snapshot of the due amount at save time, not a live sum: a load
            // edited later must not silently rewrite a receipt that was signed.
            $table->decimal('total_due', 12, 2)->default(0);
            $table->decimal('amount_received', 12, 2)->default(0);
            $table->string('currency')->default(PaymentCurrency::Usd->value);
            $table->date('payment_date');
            $table->string('reference')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('status')->default(CompanyPaymentStatus::Pending->value);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['company_id', 'period_from']);
        });

        Schema::create('company_payment_loads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('load_id')->constrained()->cascadeOnDelete();

            $table->unique(['company_payment_id', 'load_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_payment_loads');
        Schema::dropIfExists('company_payments');
    }
};
