<?php

namespace App\Http\Controllers\Company;

use App\Enums\CompanyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreCompanyRequest;
use App\Http\Requests\Company\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\User;
use App\Services\Company\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CompanyController extends Controller
{
    public function __construct(private readonly CompanyService $companyService)
    {
    }

    public function index(Request $request): View
    {
        return view('companies.index', [
            'companies' => $this->companyService->paginate($request->only('q', 'status'), viewer: $request->user()),
            'statuses' => CompanyStatus::cases(),
        ]);
    }

    public function create(): View
    {
        return view('companies.create', $this->formData());
    }

    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        $company = $this->companyService->create($request->validated());

        return redirect()
            ->route('companies.index')
            ->with('success', "Company {$company->name} created.");
    }

    public function edit(Company $company): View
    {
        return view('companies.edit', $this->formData(['company' => $company->loadCount('drivers')]));
    }

    public function update(UpdateCompanyRequest $request, Company $company): RedirectResponse
    {
        $this->companyService->update($company, $request->validated());

        return redirect()
            ->route('companies.index')
            ->with('success', "Company {$company->name} updated.");
    }

    public function search(Request $request): JsonResponse
    {
        // The default commission is the company's earning, so it only travels to
        // people who may see it.
        $withCommission = $request->user()->can('view-company-commission');

        return response()->json([
            'results' => $this->companyService->search($request->string('q')->toString(), viewer: User::allocationOwner($request->user(), $request->input('dispatcher_id')))
                ->map(fn (Company $company): array => [
                    'id' => $company->id,
                    'name' => $company->name,
                    'mc_number' => $company->mc_number,
                    'default_commission_percentage' => $withCommission ? $company->default_commission_percentage : null,
                ])->all(),
        ]);
    }

    public function destroy(Company $company): RedirectResponse
    {
        $this->companyService->delete($company);

        return redirect()
            ->route('companies.index')
            ->with('success', "Company {$company->name} removed.");
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function formData(array $extra = []): array
    {
        return [
            ...$extra,
            'statuses' => CompanyStatus::cases(),
        ];
    }
}
