<?php

namespace App\Http\Controllers\Driver;

use App\Enums\DriverStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\StoreDriverRequest;
use App\Http\Requests\Driver\UpdateDriverRequest;
use App\Http\Resources\Driver\DriverResource;
use App\Models\Driver;
use App\Models\EquipmentType;
use App\Models\User;
use App\Services\Company\CompanyService;
use App\Services\Driver\DriverService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\View\View;

final class DriverController extends Controller
{
    public function __construct(
        private readonly DriverService $driverService,
        private readonly CompanyService $companyService,
    ) {
    }

    public function index(Request $request): View
    {
        return view('drivers.index', [
            'drivers' => $this->driverService->paginate($request->only('q', 'status', 'company_id'), viewer: $request->user()),
            'statuses' => DriverStatus::cases(),
            'companies' => $this->companyService->active($request->user()),
            'selectedCompanyId' => $request->integer('company_id') ?: null,
        ]);
    }

    public function create(Request $request): View
    {
        return view('drivers.create', [
            'statuses' => DriverStatus::cases(),
            'equipmentTypes' => EquipmentType::orderBy('name')->get(['id', 'name']),
            'companies' => $this->companyService->active($request->user()),
        ]);
    }

    public function store(StoreDriverRequest $request): RedirectResponse
    {
        $driver = $this->driverService->create($request->validated());

        return redirect()
            ->route('drivers.index')
            ->with('success', "Driver {$driver->name} created.");
    }

    public function edit(Request $request, Driver $driver): View
    {
        return view('drivers.edit', [
            'driver' => $driver,
            'statuses' => DriverStatus::cases(),
            'equipmentTypes' => EquipmentType::orderBy('name')->get(['id', 'name']),
            'companies' => $this->companyService->active($request->user()),
        ]);
    }

    public function update(UpdateDriverRequest $request, Driver $driver): RedirectResponse
    {
        $this->driverService->update($driver, $request->validated());

        return redirect()
            ->route('drivers.index')
            ->with('success', "Driver {$driver->name} updated.");
    }

    public function destroy(Driver $driver): RedirectResponse
    {
        $this->driverService->delete($driver);

        return redirect()
            ->route('drivers.index')
            ->with('success', "Driver {$driver->name} removed.");
    }

    public function search(Request $request): AnonymousResourceCollection
    {
        return DriverResource::collection(
            $this->driverService->search(
                $request->string('q')->toString(),
                companyId: $request->integer('company_id') ?: null,
                includeInactive: $request->boolean('include_inactive'),
                viewer: User::allocationOwner($request->user(), $request->input('dispatcher_id')),
            )
        );
    }
}
