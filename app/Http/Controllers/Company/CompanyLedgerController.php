<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyPayment;
use App\Services\Ledger\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * A company's running account: what it has been billed, what it paid and when,
 * what is still open load by load, and any advance it is holding.
 */
final class CompanyLedgerController extends Controller
{
    public function __construct(private readonly LedgerService $ledger)
    {
    }

    public function show(Request $request, Company $company): View
    {
        $from = $this->date($request->query('from'));
        $to = $this->date($request->query('to'));

        return view('companies.ledger', [
            'company' => $company,
            'position' => $this->ledger->position($company),
            'statement' => $this->ledger->statement($company, $from, $to),
            'payments' => CompanyPayment::query()
                ->where('company_id', $company->id)
                // Cancellation adjustment lines are not receipts; they appear on the statement.
                ->where('amount_received', '>', 0)
                ->latest('payment_date')->latest('id')
                ->limit(10)
                ->get(),
        ]);
    }

    private function date(mixed $value): ?Carbon
    {
        $value = trim((string) $value);

        return $value !== '' && Carbon::hasFormat($value, 'Y-m-d') ? Carbon::parse($value) : null;
    }
}
