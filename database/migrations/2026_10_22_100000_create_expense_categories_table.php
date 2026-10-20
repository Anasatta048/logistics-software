<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Expense categories become per-company data instead of a fixed list, and an
 * expense gains a payee (who was paid) and a reference (receipt / cheque no.).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name', 80);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
            $table->index(['tenant_id', 'is_active', 'sort_order'], 'expense_categories_tenant_list_idx');
        });

        Schema::table('expenses', function (Blueprint $table): void {
            $table->unsignedBigInteger('expense_category_id')->nullable()->after('expense_date');
            $table->string('payee', 120)->nullable()->after('amount');
            $table->string('reference', 60)->nullable()->after('payee');
        });

        // Carry the old fixed values over as real categories, per tenant.
        $now = now();
        $pairs = DB::table('expenses')->select('tenant_id', 'category')->distinct()->get();
        foreach ($pairs as $pair) {
            $id = DB::table('expense_categories')->insertGetId([
                'tenant_id' => $pair->tenant_id,
                'name' => Str::headline($pair->category),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('expenses')
                ->where('category', $pair->category)
                ->where(fn ($q) => $pair->tenant_id === null ? $q->whereNull('tenant_id') : $q->where('tenant_id', $pair->tenant_id))
                ->update(['expense_category_id' => $id]);
        }

        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropIndex('expenses_category_expense_date_index');
            $table->dropColumn('category');
            $table->index(['tenant_id', 'expense_category_id', 'expense_date'], 'expenses_tenant_category_idx');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropIndex('expenses_tenant_category_idx');
            $table->string('category')->default('other')->after('expense_date');
            $table->dropColumn(['expense_category_id', 'payee', 'reference']);
            $table->index(['category', 'expense_date']);
        });

        Schema::dropIfExists('expense_categories');
    }
};
