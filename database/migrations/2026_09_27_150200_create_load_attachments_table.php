<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('load_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('load_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('type'); // App\Enums\AttachmentType
            $table->string('file_path');
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();

            $table->index(['load_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('load_attachments');
    }
};
