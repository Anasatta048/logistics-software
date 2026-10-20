<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Load flow becomes Booked → Pickup → Loaded → Delivered → Paid, drivers can
 * carry several loads (their status follows the furthest one), trucks and loads
 * get dimensions, money in/out no longer needs an account but names a payment
 * method, and admin-editable settings (the team timezones) get a home.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->decimal('truck_length_ft', 8, 2)->nullable()->after('trailer_number');
            $table->decimal('truck_width_ft', 8, 2)->nullable()->after('truck_length_ft');
            $table->decimal('truck_height_ft', 8, 2)->nullable()->after('truck_width_ft');
            $table->decimal('truck_max_weight_lbs', 10, 1)->nullable()->after('truck_height_ft');
        });

        Schema::table('loads', function (Blueprint $table) {
            $table->decimal('length_ft', 8, 2)->nullable()->after('weight_lbs');
            $table->decimal('width_ft', 8, 2)->nullable()->after('length_ft');
            $table->decimal('height_ft', 8, 2)->nullable()->after('width_ft');
        });

        // Dispatched was "in transit / delivered"; it is Delivered now.
        DB::table('loads')->where('status', 'dispatched')->update(['status' => 'delivered']);
        DB::table('load_status_histories')->where('from_status', 'dispatched')->update(['from_status' => 'delivered']);
        DB::table('load_status_histories')->where('to_status', 'dispatched')->update(['to_status' => 'delivered']);
        // Driver statuses are derived from their loads from now on.
        DB::table('drivers')->where('status', 'on_load')->update(['status' => 'loaded']);

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
        });
        Schema::table('expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id')->nullable()->change();
            $table->string('payment_method')->nullable()->after('amount');
            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::table('company_payments', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('payment_date');
        });

        Schema::table('commission_payouts', function (Blueprint $table) {
            $table->dropForeign(['payment_account_id']);
        });
        Schema::table('commission_payouts', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_account_id')->nullable()->change();
            $table->string('payment_method')->nullable()->after('pkr_amount');
            $table->foreign('payment_account_id')->references('id')->on('accounts')->restrictOnDelete();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');

        Schema::table('commission_payouts', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
        Schema::table('company_payments', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });

        DB::table('loads')->where('status', 'delivered')->update(['status' => 'dispatched']);
        DB::table('loads')->where('status', 'pickup')->update(['status' => 'booked']);
        DB::table('drivers')->whereIn('status', ['booked', 'pickup', 'loaded'])->update(['status' => 'on_load']);

        Schema::table('loads', function (Blueprint $table) {
            $table->dropColumn(['length_ft', 'width_ft', 'height_ft']);
        });
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['truck_length_ft', 'truck_width_ft', 'truck_height_ft', 'truck_max_weight_lbs']);
        });
    }
};
