<x-app-layout>
    <x-slot name="header">{{ __('Company Receivables') }}</x-slot>

    <div class="space-y-6">
        <div>
            <h1 class="text-lg font-semibold text-text-primary dark:text-white">{{ __('Company Receivables') }}</h1>
            <p class="text-sm text-text-muted">
                {{ __('Billed and Received follow the date filter. Balance due, Overdue and Credit held are as of today across every open invoice, and Not yet invoiced is commission on paid loads that no invoice has billed.') }}
            </p>
        </div>

        <x-card>
            <x-reports.filter-form action="reports.receivables" :companies="$companies" :exportable="true" />
        </x-card>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-6">
            <x-reports.stat :label="__('Billed')" :value="$totals['billed']" :money="true" />
            <x-reports.stat :label="__('Received')" :value="$totals['received']" :money="true" />
            <x-reports.stat :label="__('Balance due')" :value="$totals['balance']" :money="true" :tone="$totals['balance'] > 0 ? 'bad' : 'good'" />
            <x-reports.stat :label="__('Overdue')" :value="$totals['overdue']" :money="true" :tone="$totals['overdue'] > 0 ? 'bad' : null" />
            <x-reports.stat :label="__('Credit held')" :value="$totals['credit']" :money="true" />
            <x-reports.stat :label="__('Not yet invoiced')" :value="$totals['unbilled']" :money="true" />
        </div>

        <x-card :padded="false">
            @if ($rows->isEmpty())
                <x-empty-state icon="building" title="No company activity to report" />
            @else
                <div class="overflow-x-auto">
                    <table data-table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr>
                                <th class="px-4 py-3">Company</th>
                                <th class="px-4 py-3 text-right">Invoices</th>
                                <th class="px-4 py-3 text-right">Billed</th>
                                <th class="px-4 py-3 text-right">Received</th>
                                <th class="px-4 py-3 text-right">Balance due</th>
                                <th class="px-4 py-3 text-right">Overdue</th>
                                <th class="px-4 py-3 text-right">Credit held</th>
                                <th class="px-4 py-3 text-right">Not yet invoiced</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($rows as $row)
                                <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                    <td class="px-4 py-3">
                                        <span class="font-medium text-text-primary dark:text-white">{{ $row->company_name }}</span>
                                        <span class="block text-xs text-text-muted">{{ $row->mc_number }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">{{ $row->invoices_count }}</td>
                                    <td class="px-4 py-3 text-right">${{ number_format($row->billed_total, 2) }}</td>
                                    <td class="px-4 py-3 text-right">${{ number_format($row->received_total, 2) }}</td>
                                    <td class="px-4 py-3 text-right {{ $row->balance_total > 0 ? 'font-medium' : 'text-text-muted' }}">${{ number_format($row->balance_total, 2) }}</td>
                                    <td class="px-4 py-3 text-right {{ $row->overdue_total > 0 ? 'text-status-cancelled font-medium' : 'text-text-muted' }}">${{ number_format($row->overdue_total, 2) }}</td>
                                    <td class="px-4 py-3 text-right {{ $row->credit_total > 0 ? 'text-blue-600 dark:text-blue-300' : 'text-text-muted' }}">${{ number_format($row->credit_total, 2) }}</td>
                                    <td class="px-4 py-3 text-right {{ $row->unbilled_total > 0 ? '' : 'text-text-muted' }}">${{ number_format($row->unbilled_total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
</x-app-layout>
