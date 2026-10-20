<?php

namespace App\Http\Controllers\EquipmentType;

use App\Http\Controllers\Controller;
use App\Http\Requests\EquipmentType\StoreEquipmentTypeRequest;
use App\Http\Requests\EquipmentType\UpdateEquipmentTypeRequest;
use App\Models\EquipmentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class EquipmentTypeController extends Controller
{
    public function index(): View
    {
        return view('equipment-types.index', [
            // WithCount so the list can refuse to orphan a driver or a load.
            'equipmentTypes' => EquipmentType::query()
                ->withCount(['drivers', 'loads'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('equipment-types.create');
    }

    public function store(StoreEquipmentTypeRequest $request): RedirectResponse
    {
        $equipmentType = EquipmentType::create($request->validated());

        return redirect()
            ->route('equipment-types.index')
            ->with('success', "Equipment type {$equipmentType->name} added.");
    }

    public function edit(EquipmentType $equipmentType): View
    {
        return view('equipment-types.edit', ['equipmentType' => $equipmentType]);
    }

    public function update(UpdateEquipmentTypeRequest $request, EquipmentType $equipmentType): RedirectResponse
    {
        $equipmentType->update($request->validated());

        return redirect()
            ->route('equipment-types.index')
            ->with('success', "Equipment type {$equipmentType->name} updated.");
    }

    /**
     * The name is the key drivers and loads store, so a type that is still in
     * use cannot be deleted — those rows would be left pointing at nothing.
     * That is a business rule rather than an authorisation one, so it answers
     * with a message instead of a 403.
     */
    public function destroy(EquipmentType $equipmentType): RedirectResponse
    {
        if ($equipmentType->drivers()->exists() || $equipmentType->loads()->exists()) {
            return redirect()
                ->route('equipment-types.index')
                ->with('error', "{$equipmentType->name} is still used by a driver or a load, so it cannot be deleted. Rename it instead.");
        }

        $name = $equipmentType->name;
        $equipmentType->delete();

        return redirect()
            ->route('equipment-types.index')
            ->with('success', "Equipment type {$name} removed.");
    }
}
