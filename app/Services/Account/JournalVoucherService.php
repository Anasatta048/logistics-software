<?php

namespace App\Services\Account;

use App\Enums\JournalVoucherStatus;
use App\Enums\TransactionSource;
use App\Enums\TransactionType;
use App\Models\JournalVoucher;
use App\Models\Load;
use App\Models\User;
use App\Services\Support\AttachmentOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class JournalVoucherService
{
    public function __construct(private readonly AccountService $accounts)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): JournalVoucher
    {
        return DB::transaction(function () use ($data) {
            $jv = JournalVoucher::create([
                ...$this->withStoredFile($data),
                'status' => JournalVoucherStatus::Pending,
            ]);

            // Same trick as Load's reference_number: stamped from the primary
            // key in a second write so no sequence table is needed for one
            // number a day.
            $jv->update(['jv_number' => $this->numberFor($jv->id)]);

            return $jv;
        });
    }

    /**
     * A JV only touches balances once approved (Phase 7 DoD). Posts a debit to
     * `debit_account_id` and a credit to `credit_account_id` — the single pair
     * of ledger movements a voucher ever creates while pending.
     */
    public function approve(JournalVoucher $jv, User $approver): JournalVoucher
    {
        if (! $jv->isPending()) {
            throw new \DomainException('Only a pending journal voucher can be approved.');
        }

        return DB::transaction(function () use ($jv, $approver) {
            $this->accounts->recordTransaction(
                $jv->debitAccount,
                TransactionType::Debit,
                (float) $jv->amount,
                TransactionSource::JournalVoucher,
                $jv->id,
                "JV {$jv->jv_number}: {$jv->narration}",
                $approver,
                $jv->date?->toDateString(),
            );

            $this->accounts->recordTransaction(
                $jv->creditAccount,
                TransactionType::Credit,
                (float) $jv->amount,
                TransactionSource::JournalVoucher,
                $jv->id,
                "JV {$jv->jv_number}: {$jv->narration}",
                $approver,
                $jv->date?->toDateString(),
            );

            $jv->update([
                'status' => JournalVoucherStatus::Approved,
                'approved_by' => $approver->id,
            ]);

            return $jv->fresh();
        });
    }

    /**
     * Reverses the two postings an approved voucher made (opposite sign, same
     * accounts) and marks it voided. A no-op for a voucher that was never
     * approved — there is nothing on the ledger to undo.
     */
    public function void(JournalVoucher $jv, User $actor): JournalVoucher
    {
        return DB::transaction(function () use ($jv, $actor) {
            if ($jv->isApproved()) {
                $this->accounts->recordTransaction(
                    $jv->debitAccount,
                    TransactionType::Credit,
                    (float) $jv->amount,
                    TransactionSource::JournalVoucher,
                    $jv->id,
                    "Void JV {$jv->jv_number}: {$jv->narration}",
                    $actor,
                    $jv->date?->toDateString(),
                );

                $this->accounts->recordTransaction(
                    $jv->creditAccount,
                    TransactionType::Debit,
                    (float) $jv->amount,
                    TransactionSource::JournalVoucher,
                    $jv->id,
                    "Void JV {$jv->jv_number}: {$jv->narration}",
                    $actor,
                    $jv->date?->toDateString(),
                );
            }

            $jv->update(['status' => JournalVoucherStatus::Voided]);

            return $jv->fresh();
        });
    }

    /**
     * Voids every non-voided JV tied to a cancelled load — called from
     * `Listeners\Load\VoidFinancialEntriesOnCancellation` (Phase 3 wiring).
     */
    public function voidForLoad(Load $load, User $actor): void
    {
        JournalVoucher::query()
            ->where('load_id', $load->id)
            ->where('status', '!=', JournalVoucherStatus::Voided)
            ->get()
            ->each(fn (JournalVoucher $jv) => $this->void($jv, $actor));
    }

    /**
     * Storing the attachment is a service concern, not a validated form field
     * (same pattern as ExpenseService::withStoredFile()).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withStoredFile(array $data): array
    {
        $file = $data['attachment'] ?? null;
        unset($data['attachment']);

        if ($file instanceof UploadedFile) {
            $data['attachment_path'] = app(AttachmentOptimizer::class)->store($file, 'jv-attachments');
        }

        return $data;
    }

    private function numberFor(int $id): string
    {
        return 'JV-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }
}
