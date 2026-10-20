<?php

namespace App\Http\Controllers\Expense;

use App\Enums\ExpenseCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Expense\UpdateExpenseRequest;
use App\Models\Expense;
use App\Services\Expense\ExpenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ExpenseController extends Controller
{
    public function __construct(private readonly ExpenseService $expenses)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->only('q', 'category', 'from', 'to');

        return view('expenses.index', [
            'expenses' => $this->expenses->paginate($filters),
            'byCategory' => $this->expenses->totalsByCategory($filters),
            'categories' => ExpenseCategory::cases(),
        ]);
    }

    public function create(): View
    {
        return view('expenses.create', [
            'categories' => ExpenseCategory::cases(),
            'accounts' => \App\Models\Account::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $expense = $this->expenses->create($request->validated(), $request->user());

        return redirect()
            ->route('expenses.index')
            ->with('success', "Expense of \${$expense->amount} recorded.");
    }

    public function edit(Request $request, Expense $expense): View
    {
        return view('expenses.edit', [
            'expense' => $expense,
            'categories' => ExpenseCategory::cases(),
            'accounts' => \App\Models\Account::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->expenses->update($expense, $request->validated(), $request->user());

        return redirect()
            ->route('expenses.index')
            ->with('success', "Expense of \${$expense->amount} updated.");
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        $this->expenses->delete($expense, $request->user());

        return redirect()
            ->route('expenses.index')
            ->with('success', 'Expense removed.');
    }
}
