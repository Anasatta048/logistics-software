<?php

namespace App\Services\Account;

use App\Enums\TransactionSource;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\AccountTransfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Moves money between two own accounts as ledger postings on one transfer: out
 * of the source, into the destination and (optionally) the bank charge out of
 * the source. Reversing posts the exact opposites.
 */
final class AccountTransferService
{
    public function __construct(private readonly AccountService $accounts)
    {
    }

    /**
     * @param  array{from_account_id: int, to_account_id: int, amount: float|string, fee?: float|string|null, transfer_date: string, reference?: ?string, narration?: ?string}  $data
     */
    public function create(array $data, User $actor): AccountTransfer
    {
        if ((int) $data['from_account_id'] === (int) $data['to_account_id']) {
            throw ValidationException::withMessages(['to_account_id' => 'Pick two different accounts.']);
        }

        return DB::transaction(function () use ($data, $actor): AccountTransfer {
            // Lock both accounts in id order so two opposite transfers can not deadlock.
            $locked = Account::query()
                ->whereIn('id', [$data['from_account_id'], $data['to_account_id']])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $from = $locked[$data['from_account_id']];
            $to = $locked[$data['to_account_id']];
            $amount = round((float) $data['amount'], 2);
            $fee = round((float) ($data['fee'] ?? 0), 2);

            $transfer = AccountTransfer::create([
                ...$data,
                'amount' => $amount,
                'fee' => $fee,
                'created_by' => $actor->id,
            ]);

            $label = "Transfer #{$transfer->id}";
            $on = $transfer->transfer_date->toDateString();

            $this->accounts->recordTransaction($from, TransactionType::Credit, $amount, TransactionSource::Transfer, $transfer->id, "{$label} to {$to->name}", $actor, $on);
            $this->accounts->recordTransaction($to, TransactionType::Debit, $amount, TransactionSource::Transfer, $transfer->id, "{$label} from {$from->name}", $actor, $on);

            if ($fee > 0) {
                $this->accounts->recordTransaction($from, TransactionType::Credit, $fee, TransactionSource::Transfer, $transfer->id, "{$label} charges", $actor, $on);
            }

            return $transfer;
        });
    }

    public function reverse(AccountTransfer $transfer, User $actor): AccountTransfer
    {
        return DB::transaction(function () use ($transfer, $actor): AccountTransfer {
            $transfer = AccountTransfer::query()->whereKey($transfer->id)->lockForUpdate()->firstOrFail();

            if ($transfer->isReversed()) {
                return $transfer;
            }

            $label = "Reversal of transfer #{$transfer->id}";
            $today = now()->toDateString();

            $this->accounts->recordTransaction($transfer->from, TransactionType::Debit, (float) $transfer->amount + (float) $transfer->fee, TransactionSource::Transfer, $transfer->id, $label, $actor, $today);
            $this->accounts->recordTransaction($transfer->to, TransactionType::Credit, (float) $transfer->amount, TransactionSource::Transfer, $transfer->id, $label, $actor, $today);

            $transfer->update(['reversed_at' => now(), 'reversed_by' => $actor->id]);

            return $transfer;
        });
    }
}
