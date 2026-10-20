<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A USD receipt and its PKR conversion are one event to the people recording
 * them, so they become one record: the conversion's rate, tax and deposit
 * account move onto the company payment itself, and the separate
 * `currency_conversions` table goes away. Every existing conversion is copied
 * across first, and the account-ledger lines that pointed at a conversion are
 * re-pointed at its payment, so nothing is orphaned.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('company_payments', function (Blueprint $table) {
            // All null until the money is converted.
            $table->decimal('converted_usd', 12, 2)->nullable()->after('advance_applied');
            $table->decimal('conversion_rate', 12, 4)->nullable()->after('converted_usd');
            $table->decimal('pkr_gross', 14, 2)->nullable()->after('conversion_rate');
            $table->boolean('tax_applicable')->default(false)->after('pkr_gross');
            $table->string('tax_type')->nullable()->after('tax_applicable');
            $table->decimal('tax_value', 12, 2)->nullable()->after('tax_type');
            $table->decimal('tax_amount', 14, 2)->default(0)->after('tax_value');
            $table->decimal('pkr_net', 14, 2)->nullable()->after('tax_amount');
            $table->foreignId('deposit_account_id')->nullable()->after('pkr_net')->constrained('accounts')->restrictOnDelete();
            $table->date('conversion_date')->nullable()->after('deposit_account_id');
            $table->foreignId('converted_by')->nullable()->after('conversion_date')->constrained('users')->nullOnDelete();
            $table->dateTime('converted_at')->nullable()->after('converted_by');
            $table->text('conversion_notes')->nullable()->after('converted_at');
        });

        if (Schema::hasTable('currency_conversions')) {
            DB::statement(<<<'SQL'
                UPDATE company_payments cp
                JOIN currency_conversions cc ON cc.company_payment_id = cp.id
                SET cp.converted_usd = cc.usd_amount,
                    cp.conversion_rate = cc.conversion_rate,
                    cp.pkr_gross = cc.pkr_gross,
                    cp.tax_applicable = cc.tax_applicable,
                    cp.tax_type = cc.tax_type,
                    cp.tax_value = cc.tax_value,
                    cp.tax_amount = cc.tax_amount,
                    cp.pkr_net = cc.pkr_net,
                    cp.deposit_account_id = cc.deposit_account_id,
                    cp.conversion_date = cc.conversion_date,
                    cp.converted_by = cc.created_by,
                    cp.converted_at = cc.created_at,
                    cp.conversion_notes = cc.notes
            SQL);

            // The account ledger remembers which conversion made each deposit.
            DB::statement(<<<'SQL'
                UPDATE account_transactions t
                JOIN currency_conversions cc ON t.source_type = 'currency_conversion' AND t.source_id = cc.id
                SET t.source_id = cc.company_payment_id
            SQL);

            Schema::drop('currency_conversions');
        }
    }

    public function down(): void
    {
        Schema::create('currency_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_payment_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('usd_amount', 12, 2);
            $table->decimal('conversion_rate', 12, 4);
            $table->decimal('pkr_gross', 14, 2);
            $table->boolean('tax_applicable')->default(false);
            $table->string('tax_type')->nullable();
            $table->decimal('tax_value', 12, 2)->nullable();
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('pkr_net', 14, 2);
            $table->foreignId('deposit_account_id')->constrained('accounts')->restrictOnDelete();
            $table->date('conversion_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        DB::statement(<<<'SQL'
            INSERT INTO currency_conversions
                (company_payment_id, usd_amount, conversion_rate, pkr_gross, tax_applicable, tax_type, tax_value,
                 tax_amount, pkr_net, deposit_account_id, conversion_date, created_by, notes, created_at, updated_at)
            SELECT id, converted_usd, conversion_rate, pkr_gross, tax_applicable, tax_type, tax_value,
                   tax_amount, pkr_net, deposit_account_id, conversion_date, converted_by, conversion_notes,
                   converted_at, converted_at
            FROM company_payments WHERE converted_at IS NOT NULL
        SQL);

        Schema::table('company_payments', function (Blueprint $table) {
            $table->dropForeign(['deposit_account_id']);
            $table->dropForeign(['converted_by']);
            $table->dropColumn([
                'converted_usd', 'conversion_rate', 'pkr_gross', 'tax_applicable', 'tax_type', 'tax_value',
                'tax_amount', 'pkr_net', 'deposit_account_id', 'conversion_date', 'converted_by',
                'converted_at', 'conversion_notes',
            ]);
        });
    }
};
