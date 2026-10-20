<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        DB::table('companies')->where('payment_currency', '!=', 'usd')->update(['payment_currency' => 'usd']);
    }

    public function down(): void
    {
    }
};
