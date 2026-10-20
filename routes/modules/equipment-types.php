<?php

use App\Http\Controllers\EquipmentType\EquipmentTypeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Equipment Type Module Routes
|--------------------------------------------------------------------------
|
| Master data: the equipment list a driver and a load are typed against. The
| name is the value both tables store, so this module is the only place the
| list can be spelled — nothing else hard-codes an option.
|
*/

Route::middleware('auth')->prefix('equipment-types')->name('equipment-types.')->group(function () {
    Route::get('/', [EquipmentTypeController::class, 'index'])
        ->name('index')
        ->middleware('can:viewAny,App\Models\EquipmentType');

    Route::get('/create', [EquipmentTypeController::class, 'create'])
        ->name('create')
        ->middleware('can:create,App\Models\EquipmentType');

    Route::post('/', [EquipmentTypeController::class, 'store'])
        ->name('store')
        ->middleware('can:create,App\Models\EquipmentType');

    Route::get('/{equipmentType}/edit', [EquipmentTypeController::class, 'edit'])
        ->name('edit')
        ->middleware('can:update,equipmentType');

    Route::put('/{equipmentType}', [EquipmentTypeController::class, 'update'])
        ->name('update')
        ->middleware('can:update,equipmentType');

    Route::delete('/{equipmentType}', [EquipmentTypeController::class, 'destroy'])
        ->name('destroy')
        ->middleware('can:delete,equipmentType');
});
