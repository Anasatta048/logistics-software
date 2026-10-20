<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-tenancy, single database: every business row carries a `tenant_id`.
 *
 * Indexes follow one rule: tenant_id first. A tenant's screen always filters by
 * it, so a leading tenant_id keeps every query on a small, contiguous slice of
 * the table however many tenants share it. Single-column indexes that only held
 * a low-cardinality value (status) are replaced by tenant-leading composites.
 *
 * No foreign key to `tenants`: it would add a lookup to every insert on the
 * hottest tables, and the BelongsToTenant trait guarantees the value.
 */
return new class () extends Migration {
    /** Tables that get tenant_id plus a (tenant_id, created_at) index. */
    private const TABLES = [
        'drivers', 'brokers', 'loads', 'load_status_histories', 'load_attachments',
        'commissions', 'commission_payouts', 'expenses', 'accounts', 'account_transactions',
        'journal_vouchers', 'companies', 'company_payments', 'party_ledger_entries',
        'equipment_types', 'invoices', 'invoice_lines', 'activity_logs',
    ];

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            $table->boolean('is_super_admin')->default(false)->after('status');
            $table->index(['tenant_id', 'status']);
        });

        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) use ($name): void {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                // invoice_lines has no timestamps; it is always read through its invoice.
                $table->index($name === 'invoice_lines' ? ['tenant_id'] : ['tenant_id', 'created_at'], "{$name}_tenant_created_idx");
            });
        }

        // Starter equipment types were seeded globally by an old migration; each
        // tenant now gets its own set when it is provisioned.
        DB::table('equipment_types')->whereNull('tenant_id')->delete();

        $this->perTenantUnique('loads', 'reference_number');
        $this->perTenantUnique('journal_vouchers', 'jv_number');
        $this->perTenantUnique('brokers', 'broker_mc_number');
        $this->perTenantUnique('companies', 'mc_number');
        $this->perTenantUnique('equipment_types', 'name');
        $this->perTenantUnique('invoices', 'invoice_number');

        // Hot list filters, tenant first.
        Schema::table('loads', function (Blueprint $table): void {
            $table->dropIndex('loads_status_index');
            $table->index(['tenant_id', 'status', 'pickup_at'], 'loads_tenant_status_pickup_idx');
            $table->index(['tenant_id', 'dispatcher_id', 'status'], 'loads_tenant_dispatcher_idx');
        });
        Schema::table('drivers', function (Blueprint $table): void {
            $table->dropIndex('drivers_status_index');
            $table->index(['tenant_id', 'status'], 'drivers_tenant_status_idx');
        });
        Schema::table('journal_vouchers', function (Blueprint $table): void {
            $table->dropIndex('journal_vouchers_status_index');
            $table->index(['tenant_id', 'status', 'date'], 'jv_tenant_status_idx');
        });
        Schema::table('commissions', function (Blueprint $table): void {
            $table->dropIndex('commissions_status_index');
            $table->index(['tenant_id', 'status', 'dispatcher_id'], 'commissions_tenant_status_idx');
        });
        Schema::table('expenses', function (Blueprint $table): void {
            $table->index(['tenant_id', 'expense_date'], 'expenses_tenant_date_idx');
        });
        Schema::table('invoices', function (Blueprint $table): void {
            $table->index(['tenant_id', 'invoice_date'], 'invoices_tenant_date_idx');
        });
        Schema::table('company_payments', function (Blueprint $table): void {
            $table->index(['tenant_id', 'payment_date'], 'cp_tenant_date_idx');
        });
        Schema::table('party_ledger_entries', function (Blueprint $table): void {
            $table->index(['tenant_id', 'party_type', 'party_id', 'id'], 'ple_tenant_party_idx');
        });
        Schema::table('activity_logs', function (Blueprint $table): void {
            $table->dropIndex('activity_logs_created_at_index');
            $table->index(['tenant_id', 'module', 'created_at'], 'activity_tenant_module_idx');
        });

        // settings: one row per (tenant, key) instead of one row per key.
        Schema::drop('settings');
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('key');
            $table->json('value')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::drop('settings');
        Schema::create('settings', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        foreach (array_reverse(self::TABLES) as $name) {
            Schema::table($name, function (Blueprint $table) use ($name): void {
                $table->dropIndex("{$name}_tenant_created_idx");
                $table->dropColumn('tenant_id');
            });
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'status']);
            $table->dropColumn(['tenant_id', 'is_super_admin']);
        });
    }

    private function perTenantUnique(string $table, string $column): void
    {
        Schema::table($table, function (Blueprint $t) use ($column): void {
            $t->dropUnique([$column]);
            $t->unique(['tenant_id', $column]);
        });
    }
};
