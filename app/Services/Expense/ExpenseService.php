<?php

namespace App\Services\Expense;

use App\Enums\TransactionSource;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Expense;
use App\Models\User;
use App\Services\Account\AccountService;
use App\Services\Support\AttachmentOptimizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class ExpenseService
{
    public function __construct(private readonly AccountService $accounts)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Expense
    {
        return DB::transaction(function () use ($data, $actor) {
            $expense = Expense::create([
                ...$this->withStoredFile($data),
                // Whoever posted it is the audit trail, never the payload.
                'entered_by' => $actor->id,
            ]);

            // Money out of the chosen account. In this ledger's convention a
            // Credit is what decreases an account's balance (see
            // TransactionType::sign()), so paying an expense posts a credit
            // even though it is a debit expense line in the books.
            if ($expense->account !== null) {
                $this->accounts->recordTransaction(
                    $expense->account,
                    TransactionType::Credit,
                    (float) $expense->amount,
                    TransactionSource::Expense,
                    $expense->id,
                    "Expense #{$expense->id}: {$expense->category?->name}",
                    $actor,
                    $expense->expense_date->toDateString(),
                );
            }

            return $expense;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Expense $expense, array $data, User $actor): Expense
    {
        return DB::transaction(function () use ($expense, $data, $actor) {
            $originalAccountId = $expense->account_id;
            $originalAmount = (float) $expense->amount;
            $originalDate = $expense->expense_date->toDateString();

            // entered_by stays as posted: editing the amount is not re-entering it.
            $expense->update($this->withStoredFile($data, $expense->attachment_path));
            $expense->refresh();

            // Reverse the old posting and post the new one whenever the amount
            // or account changed — simplest correct approach, and it keeps
            // recordTransaction() the only place a balance ever moves.
            if ($originalAccountId !== $expense->account_id || $originalAmount !== (float) $expense->amount || $originalDate !== $expense->expense_date->toDateString()) {
                // An expense with no account never touched a balance, so there
                // is nothing to reverse (or to post) on that side.
                if ($originalAccountId !== null) {
                    $this->accounts->recordTransaction(
                        Account::query()->findOrFail($originalAccountId),
                        TransactionType::Debit,
                        $originalAmount,
                        TransactionSource::Expense,
                        $expense->id,
                        "Reversal of expense #{$expense->id} (edited)",
                        $actor,
                        $originalDate,
                    );
                }

                if ($expense->account !== null) {
                    $this->accounts->recordTransaction(
                        $expense->account,
                        TransactionType::Credit,
                        (float) $expense->amount,
                        TransactionSource::Expense,
                        $expense->id,
                        "Expense #{$expense->id}: {$expense->category?->name} (edited)",
                        $actor,
                        $expense->expense_date->toDateString(),
                    );
                }
            }

            return $expense;
        });
    }

    /**
     * Hard delete: the reverse of create() is the only audit trail this row
     * has, and an expense that has already been reversed costs nothing to keep
     * off the books. A soft-deleted expense would still be counted by the
     * load/expense reports unless every caller remembered to filter it out.
     */
    public function delete(Expense $expense, User $actor): void
    {
        DB::transaction(function () use ($expense, $actor) {
            // Credit back what the expense debited out, before the row disappears.
            if ($expense->account !== null) {
                $this->accounts->recordTransaction(
                    $expense->account,
                    TransactionType::Debit,
                    (float) $expense->amount,
                    TransactionSource::Expense,
                    $expense->id,
                    "Reversal of deleted expense #{$expense->id}",
                    $actor,
                    $expense->expense_date->toDateString(),
                );
            }

            $expense->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Expense>
     */
    public function paginate(array $filters, int $perPage = 500): LengthAwarePaginator
    {
        return $this->query($filters)
            ->latest('expense_date')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Sum per category for the given filter. Keyed by category id; categories
     * with nothing spent are left out (the page lists only what was spent).
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, float>
     */
    public function totalsByCategory(array $filters): array
    {
        return $this->query($filters)
            ->reorder()
            ->selectRaw('expense_category_id, COALESCE(SUM(amount), 0) AS total')
            ->groupBy('expense_category_id')
            ->toBase()
            ->get()
            ->mapWithKeys(fn (object $row): array => [(int) $row->expense_category_id => (float) $row->total])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Expense>
     */
    private function query(array $filters): Builder
    {
        $term = trim((string) ($filters['q'] ?? ''));
        $category = (int) ($filters['category'] ?? 0);
        $from = $this->boundary($filters['from'] ?? null);
        $to = $this->boundary($filters['to'] ?? null);

        return Expense::query()
            ->with(['enteredByUser:id,name', 'category:id,name', 'account:id,name'])
            ->when($term !== '', fn (Builder $query) => $query->where(fn (Builder $w) => $w
                ->where('notes', 'like', "%{$term}%")
                ->orWhere('payee', 'like', "%{$term}%")
                ->orWhere('reference', 'like', "%{$term}%")))
            ->when($category > 0, fn (Builder $query) => $query->where('expense_category_id', $category))
            ->when($from, fn (Builder $query) => $query->where('expense_date', '>=', $from->toDateString()))
            ->when($to, fn (Builder $query) => $query->where('expense_date', '<=', $to->toDateString()));
    }

    /**
     * Storing the receipt is a service concern, not a validated form field: a
     * replaced file is deleted here so the disk does not keep every draft.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withStoredFile(array $data, ?string $replacing = null): array
    {
        $file = $data['attachment'] ?? null;
        unset($data['attachment']);

        if ($file instanceof UploadedFile) {
            if ($replacing !== null) {
                Storage::disk('public')->delete($replacing);
            }

            $data['attachment_path'] = app(AttachmentOptimizer::class)->store($file, 'expense-attachments');
        }

        return $data;
    }

    /**
     * Garbage in the date boxes is ignored rather than thrown, matching the
     * loose filters the other indexes already accept.
     */
    private function boundary(mixed $value): ?Carbon
    {
        $value = trim((string) $value);

        return $value !== '' && Carbon::hasFormat($value, 'Y-m-d') ? Carbon::parse($value) : null;
    }
}
