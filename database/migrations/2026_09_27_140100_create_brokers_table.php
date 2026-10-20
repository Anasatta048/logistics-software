<?php

use App\Enums\BrokerStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('brokers', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('broker_mc_number')->unique();
            $table->string('contact_person')->nullable();
            $table->string('phone');
            $table->string('email')->nullable();
            $table->text('payment_terms')->nullable();
            $table->text('credit_notes')->nullable();
            $table->string('status')->default(BrokerStatus::Active->value);
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brokers');
    }
};
