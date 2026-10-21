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
            // Local prices; null = this plan is only priced in USD.
            $table->decimal('monthly_price_pkr', 12, 2)->nullable()->after('yearly_price');
            $table->decimal('yearly_price_pkr', 12, 2)->nullable()->after('monthly_price_pkr');
        });

        Schema::table('tenants', function (Blueprint $table) {
            // Renewal reminders: the stage (7/3/1/0 days left) already sent for the period ending `reminder_for`.
            $table->unsignedTinyInteger('reminder_stage')->nullable();
            $table->date('reminder_for')->nullable();
        });

        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('billing_cycle', 10);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->string('method', 30);
            $table->string('reference', 120)->nullable();
            $table->string('proof_path')->nullable();
            $table->text('note')->nullable();
            $table->string('status', 12)->default('pending')->index();
            $table->string('receipt_number', 30)->nullable()->unique();
            $table->text('review_note')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        // Roughly PKR 280 per USD, yearly = 10 months, for the standard plans.
        foreach (['starter' => 49, 'growth' => 129, 'enterprise' => 349] as $slug => $usd) {
            DB::table('plans')->where('slug', $slug)->update([
                'monthly_price_pkr' => $usd * 280,
                'yearly_price_pkr' => $usd * 280 * 10,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
        Schema::table('tenants', fn (Blueprint $table) => $table->dropColumn(['reminder_stage', 'reminder_for']));
        Schema::table('plans', fn (Blueprint $table) => $table->dropColumn(['monthly_price_pkr', 'yearly_price_pkr']));
    }
};
