<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('currency_conversions', function (Blueprint $table) {
            $table->id();
            // Unique, not just indexed: one USD receipt converts exactly once.
            $table->foreignId('company_payment_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('usd_amount', 12, 2);
            // Typed in by hand every time — there is no FX feed on this system,
            // so the rate that was actually used has to survive for the audit.
            $table->decimal('conversion_rate', 12, 4);
            $table->decimal('pkr_gross', 14, 2);
            $table->boolean('tax_applicable')->default(false);
            $table->string('tax_type')->nullable();
            $table->decimal('tax_value', 12, 2)->nullable();
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('pkr_net', 14, 2);
            $table->foreignId('deposit_account_id')->constrained('accounts')->restrictOnDelete();
            $table->date('conversion_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_conversions');
    }
};
