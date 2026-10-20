<?php

namespace App\Console\Commands;

use App\Enums\CommissionStatus;
use App\Enums\LedgerEntryType;
use App\Enums\LoadStatus;
use App\Models\Commission;
use App\Models\CommissionPayout;
use App\Models\Company;
use App\Models\CompanyPayment;
use App\Models\Load;
use App\Models\User;
use App\Services\Ledger\LedgerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Builds the party ledger for data that existed before it: the company
 * commission billed on Paid loads, the payments received, dispatcher
 * commissions and the payouts already made. Safe to run once; it refuses if the
 * ledger already has entries, so it can never double-count.
 */
final class BackfillLedger extends Command
{
    protected $signature = 'ledger:backfill';

    protected $description = 'Create company and dispatcher ledger entries from existing loads, payments and commissions';

    public function handle(LedgerService $ledger): int
    {
        if (DB::table('party_ledger_entries')->exists()) {
            $this->warn('The ledger already has entries — nothing done.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($ledger): void {
            $this->companies($ledger);
            $this->dispatchers($ledger);
        });

        $this->info('Ledger built: '.DB::table('party_ledger_entries')->count().' entries.');

        return self::SUCCESS;
    }

    private function companies(LedgerService $ledger): void
    {
        foreach (Company::withTrashed()->get() as $company) {
            $events = [];

            $loads = Load::query()->where('company_id', $company->id)
                ->whereHas('statusHistory', fn ($q) => $q->where('to_status', LoadStatus::Paid->value))
                ->with(['statusHistory' => fn ($q) => $q->where('to_status', LoadStatus::Paid->value)])
                ->get();

            foreach ($loads as $load) {
                // A cancelled paid load had its amount zeroed; the adjustment
                // line written at cancellation remembers what was billed.
                $billed = $load->status === LoadStatus::Paid
                    ? (float) $load->company_commission_amount
                    : abs((float) DB::table('company_payment_loads')->where('load_id', $load->id)->where('applied_amount', '<', 0)->sum('applied_amount'));

                if ($billed <= 0) {
                    continue;
                }

                $paidAt = $load->statusHistory->first()?->created_at ?? $load->updated_at;
                $events[] = ['date' => $paidAt->toDateString(), 'rank' => 1, 'type' => LedgerEntryType::CommissionAccrued, 'amount' => $billed, 'load' => $load, 'source' => $load,
                    'text' => "Commission on {$load->reference_number}"];

                if ($load->status === LoadStatus::Cancelled) {
                    $events[] = ['date' => ($load->cancelled_at ?? $paidAt)->toDateString(), 'rank' => 2, 'type' => LedgerEntryType::CommissionReversed, 'amount' => -$billed, 'load' => $load, 'source' => $load,
                        'text' => "{$load->reference_number} cancelled — commission reversed"];
                }
            }

            foreach (CompanyPayment::query()->where('company_id', $company->id)->where('amount_received', '>', 0)->get() as $payment) {
                $events[] = ['date' => $payment->payment_date->toDateString(), 'rank' => 3, 'type' => LedgerEntryType::PaymentReceived, 'amount' => -(float) $payment->amount_received, 'load' => null, 'source' => $payment,
                    'text' => "Payment #{$payment->id} received: $".number_format((float) $payment->amount_received, 2)];
            }

            $this->post($ledger, $company, $events);
        }
    }

    private function dispatchers(LedgerService $ledger): void
    {
        foreach (User::query()->whereIn('id', Commission::query()->select('dispatcher_id'))->get() as $user) {
            $events = [];

            foreach (Commission::query()->where('dispatcher_id', $user->id)->with('loadRecord')->get() as $commission) {
                $load = $commission->loadRecord;
                $amount = (float) $commission->commission_amount;

                if ($amount <= 0) {
                    continue;
                }

                $events[] = ['date' => $commission->calculated_at->toDateString(), 'rank' => 1, 'type' => LedgerEntryType::CommissionAccrued, 'amount' => $amount, 'load' => $load, 'source' => $commission,
                    'text' => "Commission on {$load->reference_number}"];

                // Paid under the old one-shot payout: turn it into a real payout.
                if ($commission->paid_at !== null && $commission->payment_account_id !== null) {
                    $payout = CommissionPayout::create([
                        'dispatcher_id' => $user->id, 'usd_amount' => $amount,
                        'conversion_rate' => $commission->conversion_rate ?? 1, 'pkr_amount' => $commission->pkr_amount ?? $amount,
                        'payment_account_id' => $commission->payment_account_id, 'paid_at' => $commission->paid_at,
                        'notes' => $commission->notes, 'paid_by' => $commission->paid_by,
                    ]);
                    $payout->commissions()->attach($commission->id, ['amount' => $amount]);

                    $events[] = ['date' => $commission->paid_at->toDateString(), 'rank' => 3, 'type' => LedgerEntryType::PayoutMade, 'amount' => -$amount, 'load' => null, 'source' => $payout,
                        'text' => "Payout #{$payout->id}: $".number_format($amount, 2)];
                }

                if ($commission->status === CommissionStatus::Reversed) {
                    $events[] = ['date' => ($commission->reversed_at ?? $commission->calculated_at)->toDateString(), 'rank' => 2, 'type' => LedgerEntryType::CommissionReversed, 'amount' => -$amount, 'load' => $load, 'source' => $commission,
                        'text' => "{$load->reference_number} cancelled — commission reversed"];
                }
            }

            $this->post($ledger, $user, $events);
        }
    }

    /**
     * Oldest first, accruals before the money that settles them on the same day,
     * so the running balance reads the way it happened.
     *
     * @param  list<array<string, mixed>>  $events
     */
    private function post(LedgerService $ledger, \Illuminate\Database\Eloquent\Model $party, array $events): void
    {
        usort($events, fn (array $a, array $b): int => [$a['date'], $a['rank']] <=> [$b['date'], $b['rank']]);

        foreach ($events as $event) {
            $ledger->post($party, $event['type'], $event['amount'], $event['text'], $event['source'], $event['load'], null, $event['date']);
        }
    }
}
