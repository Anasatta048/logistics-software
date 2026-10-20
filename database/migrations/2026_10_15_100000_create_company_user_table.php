<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Which companies a dispatcher may book loads for. Only dispatchers are
     * restricted by it; every other role sees all companies.
     */
    public function up(): void
    {
        Schema::create('company_user', function (Blueprint $table): void {
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['company_id', 'user_id']);
        });

        // Existing data keeps working: every company a dispatcher already has
        // loads for is allocated to them, so nothing they see today disappears.
        $pairs = DB::table('loads')
            ->whereNotNull('company_id')
            ->whereNotNull('dispatcher_id')
            ->whereIn('dispatcher_id', DB::table('users')->select('id'))
            ->whereIn('company_id', DB::table('companies')->select('id'))
            ->distinct()
            ->get(['company_id', 'dispatcher_id']);

        foreach ($pairs->chunk(500) as $chunk) {
            DB::table('company_user')->insertOrIgnore(
                $chunk->map(fn ($row): array => ['company_id' => $row->company_id, 'user_id' => $row->dispatcher_id])->all()
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_user');
    }
};
