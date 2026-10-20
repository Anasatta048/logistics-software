<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-company extra fields on loads, drivers and carrier companies. The
 * definitions live here; the values sit in one JSON column on the record, so
 * reading a row never needs a join.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('custom_fields', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('entity', 20);
            $table->string('key', 60);
            $table->string('label', 80);
            $table->string('type', 20);
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('show_in_list')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'entity', 'key']);
            $table->index(['tenant_id', 'entity', 'is_active', 'sort_order'], 'custom_fields_lookup_idx');
        });

        foreach (['loads', 'drivers', 'companies'] as $table) {
            Schema::table($table, function (Blueprint $t): void {
                $t->json('custom')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['loads', 'drivers', 'companies'] as $table) {
            Schema::table($table, function (Blueprint $t): void {
                $t->dropColumn('custom');
            });
        }

        Schema::dropIfExists('custom_fields');
    }
};
