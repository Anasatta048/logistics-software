<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FLOW_UPDATE_PLAN Section 3.2 / 4.1 / 8: drivers belong to a company, and the
 * load carries the company commission it accrued.
 *
 * The dropped columns are the old owner-operator assumptions — the MC number
 * moved to the company, driver pay is out of scope entirely, and the free-text
 * MC on a load is now a foreign key.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['driver_mc_dot', 'payment_details']);
        });

        Schema::table('loads', function (Blueprint $table) {
            // Denormalised on purpose: a driver can move companies later, but a
            // closed load must keep billing the company that actually ran it.
            $table->foreignId('company_id')->nullable()->after('reference_number')->constrained()->nullOnDelete();
            $table->decimal('company_commission_percentage', 5, 2)->default(0);
            $table->decimal('company_commission_amount', 12, 2)->default(0);
            $table->boolean('commission_settled')->default(false);
        });

        Schema::table('loads', function (Blueprint $table) {
            // Suggested settlement rows: "every Paid load for this company in
            // this period whose commission is not already inside a payment".
            $table->index(['company_id', 'status', 'commission_settled'], 'loads_company_settlement_idx');
        });

        Schema::table('loads', function (Blueprint $table) {
            $table->dropColumn(['company_mc_number', 'driver_pay']);
        });
    }

    public function down(): void
    {
        Schema::table('loads', function (Blueprint $table) {
            $table->string('company_mc_number')->nullable();
            $table->decimal('driver_pay', 12, 2)->default(0);
        });

        Schema::table('loads', function (Blueprint $table) {
            $table->dropIndex('loads_company_settlement_idx');
            $table->dropColumn(['company_commission_percentage', 'company_commission_amount', 'commission_settled']);
        });

        Schema::table('loads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->string('driver_mc_dot')->nullable();
            $table->text('payment_details')->nullable();
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
