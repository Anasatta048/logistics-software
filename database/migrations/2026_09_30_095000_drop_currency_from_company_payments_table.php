<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * A company settles in the currency it was billed in, which is always USD.
     * The PKR side of the money only exists once the linked currency conversion
     * is booked, so a per-receipt currency column could only ever hold one value.
     */
    public function up(): void
    {
        Schema::table('company_payments', function (Blueprint $table): void {
            $table->dropColumn('currency');
        });
    }

    public function down(): void
    {
        Schema::table('company_payments', function (Blueprint $table): void {
            $table->string('currency')->default('usd')->after('amount_received');
        });
    }
};
