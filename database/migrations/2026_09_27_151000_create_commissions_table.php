<?php

use App\Enums\CommissionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            // One commission per load: the paid event fires once, and a
            // recalculation updates this row instead of adding another.
            $table->foreignId('load_id')->unique()->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('dispatcher_id')->nullable()->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('margin_amount', 12, 2);
            // Snapshot of users.commission_percentage when the load was marked
            // paid, so a later rate change cannot rewrite history.
            $table->decimal('commission_percentage', 5, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->string('status')->default(CommissionStatus::Accrued->value)->index();
            $table->dateTime('calculated_at');
            $table->dateTime('reversed_at')->nullable();
            $table->timestamps();

            $table->index(['dispatcher_id', 'calculated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
