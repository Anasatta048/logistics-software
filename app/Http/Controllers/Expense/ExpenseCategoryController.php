<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use App\Support\Tenancy\TenantRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Settings → Expense categories: the headings expenses are filed under. */
final class ExpenseCategoryController extends Controller
{
    public function index(): View
    {
        return view('expense-categories.index', [
            'categories' => ExpenseCategory::query()->withCount('expenses')->ordered()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', TenantRule::unique('expense_categories', 'name')],
        ]);

        ExpenseCategory::create($data + ['sort_order' => (int) ExpenseCategory::query()->max('sort_order') + 1]);

        return back()->with('success', "Category {$data['name']} added.");
    }

    public function update(Request $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', TenantRule::unique('expense_categories', 'name')->ignore($expenseCategory)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $expenseCategory->update([
            'name' => $data['name'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Category saved.');
    }

    public function destroy(ExpenseCategory $expenseCategory): RedirectResponse
    {
        // History must keep its heading: a category with expenses is switched off, not deleted.
        if ($expenseCategory->expenses()->exists()) {
            return back()->with('error', "{$expenseCategory->name} has expenses, so it can only be hidden. Untick “Active” instead.");
        }

        $expenseCategory->delete();

        return back()->with('success', 'Category removed.');
    }
}
