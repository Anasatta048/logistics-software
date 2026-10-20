<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // A person's saved report view; optionally emailed on a schedule.
        Schema::create('saved_reports', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('report', 60);
            $table->string('name', 80);
            $table->json('filters')->nullable();
            $table->string('schedule', 10)->default('none');
            $table->unsignedTinyInteger('schedule_day')->default(1);
            $table->string('format', 5)->default('csv');
            $table->text('recipients')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'user_id', 'report'], 'saved_reports_lookup_idx');
            $table->index(['schedule', 'last_sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_reports');
    }
};
