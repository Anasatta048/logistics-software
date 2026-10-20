<?php

namespace App\Http\Controllers\Company;

use App\Enums\CompanyPaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyPayment\ConvertCompanyPaymentRequest;
use App\Http\Requests\CompanyPayment\StoreCompanyPaymentRequest;
use App\Models\Account;
use App\Models\Company;
use App\Models\CompanyPayment;
use App\Models\Load;
use App\Services\Company\CompanyPaymentService;
use App\Services\Company\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CompanyPaymentController extends Controller
{
    public function __construct(
        private readonly CompanyPaymentService $payments,
        private readonly CompanyService $companies,
    ) {
    }

    public function index(Request $request): View
    {
        return view('company-payments.index', [
            'payments' => $this->payments->paginate($request->only('q', 'status', 'company_id', 'from', 'to')),
            'statuses' => CompanyPaymentStatus::cases(),
            'companies' => $this->companies->active(),
            'selectedCompanyId' => $request->integer('company_id') ?: null,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        // A company payment is settled against an invoice: start there.
        if (! $request->boolean('manual')) {
            return redirect()->route('invoices.create');
        }

        return view('company-payments.create', $this->formData([
            'company' => $request->integer('company_id')
                ? Company::find($request->integer('company_id'))
                : null,
        ]));
    }

    public function store(StoreCompanyPaymentRequest $request): RedirectResponse
    {
        $payment = $this->payments->create($request->validated(), $request->user());

        return redirect()
            ->route('company-payments.show', $payment)
            ->with('success', "Payment #{$payment->id} recorded ({$payment->status->label()}).");
    }

    public function show(CompanyPayment $companyPayment): View
    {
        return view('company-payments.show', [
            'payment' => $companyPayment->load(['company', 'creator', 'depositAccount', 'converter']),
            'accounts' => Account::query()->where('status', 'active')->orderBy('name')->get(),
            'rate' => $this->payments->lastRate(),
            'loads' => $companyPayment->loads()->orderBy('pickup_at')->get(),
        ]);
    }

    public function convert(ConvertCompanyPaymentRequest $request, CompanyPayment $companyPayment): RedirectResponse
    {
        $this->payments->convert($companyPayment, $request->validated(), $request->user());

        return redirect()
            ->route('company-payments.show', $companyPayment)
            ->with('success', "Payment #{$companyPayment->id} converted to PKR and deposited.");
    }

    public function edit(CompanyPayment $companyPayment): View
    {
        return view('company-payments.edit', $this->formData([
            'payment' => $companyPayment->load('company'),
            'selectedLoads' => $companyPayment->loads()->orderBy('pickup_at')->get(),
        ]));
    }

    public function update(StoreCompanyPaymentRequest $request, CompanyPayment $companyPayment): RedirectResponse
    {
        $this->payments->update($companyPayment, $request->validated(), $request->user());

        return redirect()
            ->route('company-payments.show', $companyPayment)
            ->with('success', "Payment #{$companyPayment->id} updated.");
    }

    /**
     * Preview of the receipt the service will build: the company's oldest unpaid
     * commission, oldest load first, and how much is still owed on each. Read
     * only — the form has nothing to pick, the allocation is decided server side.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'company_id' => ['required', 'integer', \App\Support\Tenancy\TenantRule::exists('companies', 'id')],
            'period_from' => ['nullable', 'date'],
            'period_to' => ['nullable', 'date'],
        ]);

        $loads = $this->payments->suggestableLoads(
            Company::findOrFail((int) $filters['company_id']),
            $filters['period_from'] ?? null,
            $filters['period_to'] ?? null,
        );

        return response()->json([
            'loads' => $loads->map(fn (Load $load): array => [
                'id' => $load->id,
                'reference_number' => $load->reference_number,
                'pickup_at' => $load->pickup_at->toDateString(),
                'outstanding' => (float) $load->outstanding_commission,
            ])->all(),
            'total_due' => round($loads->sum(fn (Load $load): float => (float) $load->outstanding_commission), 2),
        ]);
    }

    /**
     * What the company still owes, and what it has paid ahead. The form calls
     * this as soon as a company is picked, so the person typing the amount can
     * see what the money is for — and whether it is an overpayment.
     */
    public function balance(Request $request): JsonResponse
    {
        $company = Company::findOrFail($request->integer('company_id'));

        return response()->json($this->payments->balance($company));
    }

    /**
     * `selectedLoads` is null on create — the form falls back to fetching them
     * for the company/period the user picks. It is always defined so the form
     * partial needs no isset() dance.
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function formData(array $extra = []): array
    {
        return [
            'selectedLoads' => null,
            ...$extra,
            'companies' => $this->companies->active(),
            // For the optional "Convert to PKR" section.
            'accounts' => Account::query()->where('status', 'active')->orderBy('name')->get(),
            'rate' => $this->payments->lastRate(),
        ];
    }
}
