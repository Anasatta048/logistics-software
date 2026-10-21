<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** table => [index name => columns]: filters the reports, aging, settlements and demo limits run on. */
    private const INDEXES = [
        'loads' => [
            'loads_tenant_broker_status_idx' => ['tenant_id', 'broker_id', 'status'],
            'loads_tenant_company_status_pickup_idx' => ['tenant_id', 'company_id', 'status', 'pickup_at'],
            'loads_driver_status_pickup_idx' => ['driver_id', 'status', 'pickup_at'],
        ],
        'load_status_histories' => [
            'lsh_load_to_status_created_idx' => ['load_id', 'to_status', 'created_at'],
        ],
        'notifications' => [
            'notifications_notifiable_read_idx' => ['notifiable_type', 'notifiable_id', 'read_at'],
        ],
        'demo_sessions' => [
            'demo_sessions_ip_started_idx' => ['ip_hash', 'started_at'],
        ],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            foreach ($indexes as $name => $columns) {
                if (Schema::hasTable($table) && ! Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $t) => $t->index($columns, $name));
                }
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            foreach (array_keys($indexes) as $name) {
                if (Schema::hasTable($table) && Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
                }
            }
        }
    }
};
