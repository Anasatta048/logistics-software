<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A broker is just the company (name + MC number). Who to call there changes
 * with every load, so the contact now lives on the load. Brokers are only
 * active or inactive; the blacklist is gone.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('loads', function (Blueprint $table) {
            $table->string('broker_contact_person')->nullable()->after('broker_id');
            $table->string('broker_phone', 32)->nullable()->after('broker_contact_person');
            $table->string('broker_email')->nullable()->after('broker_phone');
        });

        // Existing loads keep the contact the broker had when they were booked.
        DB::statement('UPDATE loads INNER JOIN brokers ON brokers.id = loads.broker_id
            SET loads.broker_contact_person = brokers.contact_person,
                loads.broker_phone = brokers.phone,
                loads.broker_email = brokers.email');

        DB::table('brokers')->where('status', 'blacklisted')->update(['status' => 'inactive']);

        Schema::table('brokers', function (Blueprint $table) {
            $table->dropColumn(['contact_person', 'phone', 'email']);
        });
    }

    public function down(): void
    {
        Schema::table('brokers', function (Blueprint $table) {
            $table->string('contact_person')->nullable();
            $table->string('phone')->default('');
            $table->string('email')->nullable();
        });

        Schema::table('loads', function (Blueprint $table) {
            $table->dropColumn(['broker_contact_person', 'broker_phone', 'broker_email']);
        });
    }
};
