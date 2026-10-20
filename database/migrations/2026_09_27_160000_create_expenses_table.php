<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('expense_date');
            $table->string('category')->default('other');
            $table->decimal('amount', 12, 2);
            // Company overhead, or a cost booked against one load — the second
            // case is what feeds the commission margin.
            $table->foreignId('load_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('attachment_path')->nullable();
            $table->foreignId('entered_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['category', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
