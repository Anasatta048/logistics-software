<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('load_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('load_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('from_status')->nullable(); // null on creation
            $table->string('to_status');
            $table->foreignId('changed_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['load_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('load_status_histories');
    }
};
