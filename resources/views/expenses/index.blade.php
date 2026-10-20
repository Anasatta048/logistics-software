<x-app-layout>
    <x-slot name="header">{{ __('Expenses') }}</x-slot>

    <div class="space-y-6">
        <div class="form-page-head !mb-0 flex-wrap">
            <span class="form-page-icon"><x-icon name="receipt" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Expenses') }}</h1>
                <p class="text-sm text-text-muted">
                    {{ __('Company running costs, in the account currency the money actually left.') }}
                </p>
            </div>
            @can('create', App\Models\Expense::class)
                <x-button variant="primary" as="a" href="{{ route('expenses.create') }}">
                    <x-icon name="plus" :size="16" /> {{ __('Add Expense') }}
                </x-button>
            @endcan
        </div>

        @if (session('success'))
            <x-alert variant="success">{{ session('success') }}</x-alert>
        @endif

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ($categories as $category)
                @continue(($byCategory[$category->value] ?? 0.0) == 0.0)
                <x-card class="px-4 py-3">
                    <div class="text-xs uppercase tracking-wider text-text-muted">{{ $category->label() }}</div>
                    <div class="text-xl font-semibold text-text-primary dark:text-white">
                        ${{ number_format((float) $byCategory[$category->value], 2) }}
                    </div>
                </x-card>
            @endforeach
        </div>

        <x-card :padded="false">
            @if ($expenses->isEmpty())
                <x-empty-state icon="receipt" title="No expenses found" description="Try a different filter, or log your first expense.">
                    <x-slot name="action">
                        @can('create', App\Models\Expense::class)
                            <x-button variant="primary" as="a" href="{{ route('expenses.create') }}">{{ __('Add Expense') }}</x-button>
                        @endcan
                    </x-slot>
                </x-empty-state>
            @else
                <div class="overflow-x-auto">
                    <table data-table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr>
                                <th data-filter="date" class="px-4 py-3">Date</th>
                                <th data-filter="select" class="px-4 py-3">Category</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                                <th data-filter="select" class="px-4 py-3">Method</th>
                                <th data-filter="select" class="px-4 py-3">Entered By</th>
                                <th class="px-4 py-3">Notes</th>
                                <th class="px-4 py-3">Receipt</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($expenses as $expense)
                                <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 whitespace-nowrap" data-date="{{ $expense->expense_date->toDateString() }}">
                                        {{ $expense->expense_date->format('M j, Y') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-status-badge :status="$expense->category" />
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-text-primary dark:text-white">
                                        ${{ number_format((float) $expense->amount, 2) }}
                                    </td>
                                    <td class="px-4 py-3">{{ $expense->payment_method?->label() ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $expense->enteredByUser?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-text-muted">{{ $expense->notes ?? '—' }}</td>
                                    <td class="px-4 py-3 text-text-muted">
                                        {{ $expense->attachment_path ? __('Attached') : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-3">
                                            @can('update', $expense)
                                                <a href="{{ route('expenses.edit', $expense) }}" class="text-text-muted hover:text-brand-primary" title="{{ __('Edit') }}">
                                                    <x-icon name="pencil" :size="16" />
                                                </a>
                                            @endcan
                                            @can('delete', $expense)
                                                <form method="POST" action="{{ route('expenses.destroy', $expense) }}" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-text-muted hover:text-status-cancelled" title="{{ __('Remove') }}"
                                                        data-confirm="Remove this expense?">
                                                        <x-icon name="trash" :size="16" />
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        <div>{{ $expenses->links() }}</div>
    </div>
</x-app-layout>
