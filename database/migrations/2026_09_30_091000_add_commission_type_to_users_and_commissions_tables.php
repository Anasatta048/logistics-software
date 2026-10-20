<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A dispatcher's cut is either a share of the company commission or a flat
 * amount per dispatch — same TaxType shape, so the user form offers one
 * dropdown instead of two radio blocks.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('commission_type')->default('percentage')->after('commission_percentage');
            $table->decimal('commission_fixed_amount', 12, 2)->default(0)->after('commission_type');
        });

        // Snapshotted on the commission alongside the percentage, so a row
        // keeps reading the same way after the user's setting changes.
        Schema::table('commissions', function (Blueprint $table) {
            $table->string('commission_type')->default('percentage')->after('commission_percentage');
            $table->decimal('commission_fixed_amount', 12, 2)->default(0)->after('commission_type');
        });
    }

    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->dropColumn(['commission_type', 'commission_fixed_amount']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['commission_type', 'commission_fixed_amount']);
        });
    }
};
