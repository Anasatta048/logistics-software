<?php

namespace App\Http\Controllers\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\RecordInvoicePaymentRequest;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\Load;
use App\Services\Company\CompanyPaymentService;
use App\Services\Invoice\InvoiceService;
use App\Support\Timezones;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

final class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoices,
        private readonly CompanyPaymentService $payments,
    ) {
    }

    public function index(Request $request): View
    {
        return view('invoices.index', [
            'invoices' => $this->invoices->paginate($request->only('q', 'from', 'to', 'status')),
            'statuses' => \App\Enums\CompanyPaymentStatus::cases(),
        ]);
    }

    public function create(): View
    {
        $today = Timezones::today();

        return view('invoices.create', [
            'today' => $today,
            'dueDate' => now(Timezones::us())->addDays((int) config('invoice.due_days'))->toDateString(),
            'weekStart' => now(Timezones::us())->startOfWeek()->toDateString(),
        ]);
    }

    /**
     * The loads the create form lists once a driver and a date range are picked.
     */
    public function loads(Request $request): JsonResponse
    {
        $data = $request->validate([
            'driver_id' => ['required', 'integer', \App\Support\Tenancy\TenantRule::exists('drivers', 'id')],
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $driver = Driver::query()->with('company:id,name,mc_number')->findOrFail($data['driver_id']);

        return response()->json([
            'company' => ['name' => $driver->company?->name, 'mc_number' => $driver->company?->mc_number],
            'carryover' => $driver->company ? $this->invoices->carryOver($driver->company) : null,
            'data' => $this->invoices->eligibleLoads($driver, $data['from'], $data['to'])
                ->map(fn (Load $load): array => [
                    'id' => $load->id,
                    'reference' => $load->reference_number,
                    'route' => $load->origin_address.' → '.$load->destination_address,
                    'date' => Timezones::toUs($load->pickup_at)->format('M j, Y'),
                    'status' => $load->status->label(),
                    'miles' => (float) $load->total_miles,
                    'rate' => (float) $load->rate,
                    'percentage' => (float) $load->company_commission_percentage,
                    'fee' => (float) $load->company_commission_amount,
                ])->values(),
        ]);
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $invoice = $this->invoices->create($request->validated(), $request->user());

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', "Invoice #{$invoice->invoice_number} created.");
    }

    public function show(Invoice $invoice): View
    {
        return view('invoices.show', [
            'invoice' => $invoice->load(['company', 'driver', 'lines', 'creator', 'payments']),
            'today' => Timezones::today(),
        ]);
    }

    /**
     * Money received against this invoice. It goes through the normal company
     * payment flow (ledger, advance, PKR conversion afterwards), tagged with the
     * invoice so the status here follows it.
     */
    public function pay(RecordInvoicePaymentRequest $request, Invoice $invoice): RedirectResponse
    {
        $balance = $invoice->balance();
        $payment = $this->payments->create([
            ...$request->validated(),
            'company_id' => $invoice->company_id,
            'invoice_id' => $invoice->id,
            'period_from' => $invoice->period_from->toDateString(),
            'period_to' => $invoice->period_to->toDateString(),
        ], $request->user());

        $diff = round((float) $payment->amount_received - $balance, 2);
        $outcome = match (true) {
            $diff > 0 => '$'.number_format($diff, 2).' extra is kept as advance credit.',
            $diff < 0 => '$'.number_format(-$diff, 2).' is still owed.',
            default => 'The invoice is settled in full.',
        };

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Payment of $'.number_format((float) $payment->amount_received, 2).' recorded. '.$outcome);
    }

    /**
     * ?inline=1 opens the PDF in the browser (also what the preview frame uses);
     * otherwise it downloads.
     */
    public function pdf(Request $request, Invoice $invoice): Response
    {
        $pdf = $this->invoices->pdf($invoice);

        return $request->boolean('inline')
            ? $pdf->stream($invoice->fileName())
            : $pdf->download($invoice->fileName());
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->invoices->delete($invoice);

        return redirect()
            ->route('invoices.index')
            ->with('success', "Invoice #{$invoice->invoice_number} deleted. Its loads can be invoiced again.");
    }
}
