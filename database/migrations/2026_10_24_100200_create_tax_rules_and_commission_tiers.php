<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Named taxes a company deducts when USD is converted (withholding, sales tax...).
        Schema::create('tax_rules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name', 80);
            $table->string('type', 20)->default('percentage');
            $table->decimal('value', 12, 4)->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
        });

        // Slabs for dispatcher commission: the share of monthly margin from `from_amount` up earns `rate` %.
        Schema::create('commission_tiers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->decimal('from_amount', 14, 2);
            $table->decimal('rate', 6, 2);
            $table->timestamps();

            $table->unique(['tenant_id', 'from_amount']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_tiers');
        Schema::dropIfExists('tax_rules');
    }
};
