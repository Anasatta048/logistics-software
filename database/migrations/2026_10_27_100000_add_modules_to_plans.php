<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // null = every module (custom plans keep working until edited).
            $table->json('modules')->nullable()->after('features');
        });

        $growth = ['reports', 'accounts', 'expenses', 'journal_vouchers', 'company_payments', 'commissions', 'roles', 'data'];

        foreach ([
            'starter' => ['reports'],
            'growth' => $growth,
            'enterprise' => [...$growth, 'advanced_reports', 'activity_log'],
        ] as $slug => $modules) {
            DB::table('plans')->where('slug', $slug)->update(['modules' => json_encode($modules)]);
        }
    }

    public function down(): void
    {
        Schema::table('plans', fn (Blueprint $table) => $table->dropColumn('modules'));
    }
};
