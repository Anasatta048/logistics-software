<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Driver invoices: Dreamelect bills a company for its commission on one
 * driver's loads over a date range. The invoice total is the commission, never
 * the full load rate (the gross is kept only to print "total weekly gross").
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('address')->nullable()->after('mc_number');
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('invoice_number')->unique();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->string('driver_name');
            $table->date('invoice_date');
            $table->date('due_date');
            $table->date('period_from');
            $table->date('period_to');
            $table->string('dispatcher_name')->nullable();
            $table->string('dispatcher_phone')->nullable();
            // What the loads paid in full (informational) and what we bill (commission).
            $table->decimal('gross_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'invoice_date']);
        });

        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            // One load can sit on one invoice only; deleting the invoice frees it.
            $table->foreignId('load_id')->unique()->constrained()->restrictOnDelete();
            $table->date('load_date');
            $table->string('description');
            $table->decimal('miles', 10, 1)->default(0);
            $table->decimal('rate', 12, 2);
            $table->decimal('percentage', 5, 2);
            $table->decimal('fee', 12, 2);
        });

        $now = now();
        foreach (['invoices.view', 'invoices.create', 'invoices.delete'] as $name) {
            $exists = DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->exists();

            if (! $exists) {
                DB::table('permissions')->insert(['name' => $name, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        $ids = DB::table('permissions')->whereIn('name', ['invoices.view', 'invoices.create', 'invoices.delete'])->pluck('id');

        foreach (DB::table('roles')->whereIn('name', ['admin', 'accountant'])->pluck('id') as $roleId) {
            foreach ($ids as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $roleId]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('invoices');

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('address');
        });

        $ids = DB::table('permissions')->where('name', 'like', 'invoices.%')->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
