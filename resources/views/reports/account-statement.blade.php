@php
    $credit = $transactions->filter(fn ($t) => $t->type === \App\Enums\TransactionType::Credit)->sum('amount');
    $debit = $transactions->filter(fn ($t) => $t->type === \App\Enums\TransactionType::Debit)->sum('amount');
@endphp
<x-app-layout>
    <x-slot name="header">{{ __('Account Statement') }}</x-slot>

    <div class="space-y-6">
        <div>
            <h1 class="text-lg font-semibold text-text-primary dark:text-white">{{ __('Account Statement') }}</h1>
            <p class="text-sm text-text-muted">
                {{ __('Every transaction on one account in the window, oldest first, with the running balance after each.') }}
            </p>
        </div>

        <x-card>
            <x-reports.filter-form action="reports.account-statement" :accounts="$accounts" :selectedAccountId="$selectedAccountId" :exportable="true" />
        </x-card>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-reports.stat :label="__('Transactions')" :value="$transactions->count()" />
            <x-reports.stat :label="__('Money in')" :value="number_format((float) $credit, 2)" :tone="$credit > 0 ? 'good' : null" />
            <x-reports.stat :label="__('Money out')" :value="number_format((float) $debit, 2)" :tone="$debit > 0 ? 'bad' : null" />
            <x-reports.stat :label="__('Closing balance')" :value="$transactions->isEmpty() ? '—' : number_format((float) $transactions->last()->running_balance, 2)" />
        </div>

        <x-card :padded="false">
            @if ($transactions->isEmpty())
                <x-empty-state icon="wallet" title="No transactions in this range" />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Description</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                                <th class="px-4 py-3 text-right">Running balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($transactions as $t)
                                <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 whitespace-nowrap"><x-datetime :value="$t->created_at" /></td>
                                    <td class="px-4 py-3">{{ $t->type->label() }}</td>
                                    <td class="px-4 py-3">{{ $t->description }}</td>
                                    <td class="px-4 py-3 text-right {{ $t->type === \App\Enums\TransactionType::Credit ? 'text-status-paid' : 'text-status-cancelled' }}">
                                        {{ $t->type === \App\Enums\TransactionType::Credit ? '+' : '−' }}{{ number_format((float) $t->amount, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium">{{ number_format((float) $t->running_balance, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
</x-app-layout>
