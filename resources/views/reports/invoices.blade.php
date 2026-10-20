<x-app-layout>
    <x-slot name="header">{{ __('Invoice Register') }}</x-slot>

    <div class="space-y-6">
        <div>
            <h1 class="text-lg font-semibold text-text-primary dark:text-white">{{ __('Invoice Register') }}</h1>
            <p class="text-sm text-text-muted">
                {{ __('Every invoice dated in the window with what was billed, what has been paid and what is still due. An invoice whose balance moved onto a newer one shows as carried forward, so nothing is counted twice.') }}
            </p>
        </div>

        <x-card>
            <x-reports.filter-form action="reports.invoices" :statuses="$states" :companies="$companies" :drivers="$drivers" :exportable="true" />
        </x-card>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-reports.stat :label="__('Billed')" :value="$totals['billed']" :money="true" :hint="$totals['count'].' '.__('invoices')" />
            <x-reports.stat :label="__('Paid')" :value="$totals['paid']" :money="true" />
            <x-reports.stat :label="__('Balance due')" :value="$totals['balance']" :money="true" :tone="$totals['balance'] > 0 ? 'bad' : 'good'" />
            <x-reports.stat :label="__('Overdue')" :value="$totals['overdue']" :money="true" :tone="$totals['overdue'] > 0 ? 'bad' : null" />
        </div>

        <x-card :padded="false">
            @if ($rows->isEmpty())
                <x-empty-state icon="file-text" title="No invoices match this filter" />
            @else
                <div class="overflow-x-auto">
                    <table data-table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr>
                                <th class="px-4 py-3">Invoice</th>
                                <th class="px-4 py-3">Company</th>
                                <th class="px-4 py-3">Driver</th>
                                <th class="px-4 py-3">Invoice date</th>
                                <th class="px-4 py-3">Due date</th>
                                <th class="px-4 py-3 text-right">Billed</th>
                                <th class="px-4 py-3 text-right">Paid</th>
                                <th class="px-4 py-3 text-right">Balance</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($rows as $row)
                                <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 font-medium text-text-primary dark:text-white">
                                        <a href="{{ route('invoices.show', $row->invoice) }}" class="hover:underline">#{{ $row->invoice->invoice_number }}</a>
                                    </td>
                                    <td class="px-4 py-3">{{ $row->invoice->company?->name ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $row->invoice->driver?->name ?? $row->invoice->driver_name }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $row->invoice->invoice_date->format('M j, Y') }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap {{ $row->overdue ? 'text-status-cancelled font-medium' : '' }}">
                                        {{ $row->invoice->due_date->format('M j, Y') }}
                                        @if ($row->overdue)
                                            <span class="block text-xs">{{ $row->days_overdue }} {{ __('days late') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">${{ number_format($row->due, 2) }}</td>
                                    <td class="px-4 py-3 text-right">${{ number_format($row->paid, 2) }}</td>
                                    <td class="px-4 py-3 text-right {{ $row->balance > 0 ? 'font-medium' : 'text-text-muted' }}">${{ number_format($row->balance, 2) }}</td>
                                    <td class="px-4 py-3">
                                        @if ($row->overdue)
                                            <span class="inline-flex rounded-full bg-status-cancelled/10 px-2 py-1 text-xs font-semibold text-status-cancelled">{{ __('Overdue') }}</span>
                                        @elseif ($row->carried)
                                            <span class="inline-flex rounded-full bg-border/60 px-2 py-1 text-xs font-semibold text-text-muted dark:bg-white/10">{{ __('Carried forward') }}</span>
                                        @else
                                            <x-status-badge :status="\App\Enums\CompanyPaymentStatus::from($row->state)" />
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
</x-app-layout>
