<?php

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default(AccountType::Bank->value);
            $table->decimal('opening_balance', 14, 2)->default(0);
            // Kept in step with account_transactions by AccountService::recordTransaction()
            // and never by a live SUM(), so a ledger page and a balance can be
            // read without trusting each other.
            $table->decimal('current_balance', 14, 2)->default(0);
            $table->string('status')->default(AccountStatus::Active->value);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
