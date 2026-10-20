<?php

use App\Enums\CompanyStatus;
use App\Enums\PaymentCurrency;
use App\Enums\PaymentFrequency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // The MC number identifies the carrier, not the driver behind the
            // wheel, so it lives on the company from here on.
            $table->string('mc_number')->unique();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->decimal('default_commission_percentage', 5, 2)->default(0);
            $table->string('payment_frequency')->default(PaymentFrequency::Weekly->value);
            $table->string('payment_currency')->default(PaymentCurrency::Usd->value);
            $table->string('status')->default(CompanyStatus::Active->value);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
