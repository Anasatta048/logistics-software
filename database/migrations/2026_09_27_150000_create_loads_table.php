<?php

use App\Enums\LoadStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('loads', function (Blueprint $table) {
            $table->id();
            // Null until the row exists, then filled in from the id (LoadService).
            $table->string('reference_number')->nullable()->unique();
            $table->string('company_mc_number');
            $table->foreignId('broker_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('equipment_type'); // App\Enums\EquipmentType
            $table->string('origin_address');
            $table->decimal('origin_lat', 10, 7)->nullable();
            $table->decimal('origin_lng', 10, 7)->nullable();
            $table->string('destination_address');
            $table->decimal('destination_lat', 10, 7)->nullable();
            $table->decimal('destination_lng', 10, 7)->nullable();
            $table->decimal('total_miles', 10, 1);
            $table->decimal('weight_lbs', 10, 1);
            $table->dateTime('pickup_at');
            $table->dateTime('dropoff_at');
            $table->decimal('rate', 12, 2);
            $table->decimal('rate_per_mile', 10, 2)->default(0);
            $table->decimal('driver_pay', 12, 2)->default(0);
            $table->foreignId('dispatcher_id')->nullable()->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('status')->default(LoadStatus::Booked->value)->index();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'pickup_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loads');
    }
};
