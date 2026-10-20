<?php

use App\Enums\DriverStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('truck_number');
            $table->string('trailer_number')->nullable();
            $table->string('equipment_type'); // App\Enums\EquipmentType
            $table->string('driver_mc_dot')->nullable();
            $table->string('phone');
            $table->string('email')->nullable();
            $table->text('payment_details')->nullable();
            $table->string('status')->default(DriverStatus::Available->value);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
