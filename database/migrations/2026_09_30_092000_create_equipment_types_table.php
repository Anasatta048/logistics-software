<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Equipment types become data instead of an enum, because the list has to be
 * editable from the UI. The stored value IS the name, so the dropdown, the
 * display and the validation all read the same string with no join between
 * them. The enum's values are folded into the names here so every existing
 * driver and load keeps a type that still resolves.
 *
 * ponytail: renaming a type leaves the rows already using the old name
 * untouched. Promote these columns to real foreign keys keyed on an immutable
 * id if renaming has to cascade.
 */
return new class () extends Migration {
    /** @var array<string, string> name => the enum value it replaces */
    private const STARTER = [
        'Dry Van' => 'dry_van',
        'Reefer' => 'reefer',
        'Flatbed' => 'flatbed',
        'Power Only' => 'power_only',
        'Box Truck' => 'box_truck',
    ];

    public function up(): void
    {
        Schema::create('equipment_types', function (Blueprint $table) {
            $table->id();
            // Unique because this string is the key: drivers and loads store it
            // directly, so two rows differing only in case would be ambiguous.
            $table->string('name')->unique();
            $table->timestamps();
        });

        $now = now();

        foreach (self::STARTER as $name => $oldValue) {
            DB::table('equipment_types')->insert(['name' => $name, 'created_at' => $now, 'updated_at' => $now]);
            DB::table('drivers')->where('equipment_type', $oldValue)->update(['equipment_type' => $name]);
            DB::table('loads')->where('equipment_type', $oldValue)->update(['equipment_type' => $name]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_types');
    }
};
