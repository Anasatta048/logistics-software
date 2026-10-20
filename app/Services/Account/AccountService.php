<?php

namespace App\Services\Account;

use App\Enums\TransactionSource;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The single choke point for touching an account's balance (Phase 7 DoD).
 * Every other module (Expense, JournalVoucher, future Commission payouts)
 * must go through recordTransaction() — nothing else is allowed to write to
 * `accounts.current_balance` directly.
 */
final class AccountService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Account
    {
        $data['current_balance'] = $data['opening_balance'] ?? 0;

        return Account::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Account $account, array $data): Account
    {
        // Opening balance and current balance are not editable here: changing
        // the opening balance after transactions exist would desync the
        // ledger, which is exactly what this service exists to prevent.
        unset($data['opening_balance'], $data['current_balance']);

        $account->update($data);

        return $account;
    }

    /**
     * Posts one ledger movement and keeps `current_balance` in lockstep with
     * it. Locks the account row for the duration of the write so concurrent
     * postings cannot compute the same `running_balance` twice.
     */
    public function recordTransaction(
        Account $account,
        TransactionType $type,
        float $amount,
        TransactionSource $sourceType,
        ?int $sourceId,
        string $description,
        ?User $actor = null,
    ): AccountTransaction {
        return DB::transaction(function () use ($account, $type, $amount, $sourceType, $sourceId, $description, $actor) {
            /** @var Account $locked */
            $locked = Account::query()->whereKey($account->id)->lockForUpdate()->firstOrFail();

            $newBalance = (float) $locked->current_balance + ($type->sign() * $amount);

            $locked->update(['current_balance' => $newBalance]);
            $account->setAttribute('current_balance', $newBalance);

            return AccountTransaction::create([
                'account_id' => $locked->id,
                'type' => $type,
                'amount' => $amount,
                'running_balance' => $newBalance,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'description' => $description,
                'created_by' => $actor?->id,
            ]);
        });
    }

    /**
     * Posts the exact opposite of a previously recorded transaction — used to
     * reverse an expense delete or a voided journal voucher without ever
     * mutating or deleting the original ledger row (audit trail, Section 0.1).
     */
    public function reverseTransaction(AccountTransaction $original, string $description, ?User $actor = null): AccountTransaction
    {
        return $this->recordTransaction(
            $original->account,
            $original->type->opposite(),
            (float) $original->amount,
            $original->source_type,
            $original->source_id,
            $description,
            $actor,
        );
    }

    /**
     * @return Collection<int, AccountTransaction>
     */
    public function ledgerFor(Account $account, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        /** @var Collection<int, AccountTransaction> $transactions */
        $transactions = $account->transactions()
            ->when($from, fn ($query) => $query->whereDate('created_at', '>=', $from->toDateString()))
            ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to->toDateString()))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return $transactions;
    }

    public function delete(Account $account): void
    {
        $account->delete();
    }
}
