<?php

use App\Enums\UserStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('status')->default(UserStatus::Active->value)->after('phone');
            // ponytail: one default % per user. SRD 8.1's "effective date"
            // history is not modelled — Phase 5 snapshots the % on each
            // commission row, add a user_commission_percentages table if the
            // business starts changing % mid-quarter and needs the old value.
            $table->decimal('commission_percentage', 5, 2)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'status', 'commission_percentage']);
        });
    }
};
