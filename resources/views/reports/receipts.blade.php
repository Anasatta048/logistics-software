<x-app-layout>
    <x-slot name="header">{{ __('Payment Receipts') }}</x-slot>

    <div class="space-y-6">
        <div>
            <h1 class="text-lg font-semibold text-text-primary dark:text-white">{{ __('Payment Receipts') }}</h1>
            <p class="text-sm text-text-muted">
                {{ __('Every payment received in the window. "Owed" is what the company owed when it paid, so a short payment shows its shortfall.') }}
            </p>
        </div>

        <x-card>
            <x-reports.filter-form action="reports.receipts" :statuses="collect($statuses)->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()"
                :companies="$companies" :exportable="true" />
        </x-card>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-reports.stat :label="__('Receipts')" :value="$totals['count']" />
            <x-reports.stat :label="__('Received')" :value="$totals['received']" :money="true" />
            <x-reports.stat :label="__('Shortfall')" :value="$totals['shortfall']" :money="true" :tone="$totals['shortfall'] > 0 ? 'bad' : null" />
            <x-reports.stat :label="__('Converted to PKR')" :value="$totals['converted']" :hint="__('of').' '.$totals['count']" />
        </div>

        <x-card :padded="false">
            @if ($rows->isEmpty())
                <x-empty-state icon="wallet" title="No receipts match this filter" />
            @else
                <div class="overflow-x-auto">
                    <table data-table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr>
                                <th class="px-4 py-3">Receipt</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Company</th>
                                <th class="px-4 py-3">Invoice</th>
                                <th class="px-4 py-3">Method</th>
                                <th class="px-4 py-3 text-right">Owed</th>
                                <th class="px-4 py-3 text-right">Received</th>
                                <th class="px-4 py-3 text-right">Shortfall</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">PKR</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($rows as $payment)
                                <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 font-medium text-text-primary dark:text-white">
                                        <a href="{{ route('company-payments.show', $payment) }}" class="hover:underline">#{{ $payment->id }}</a>
                                        @if ($payment->reference)
                                            <span class="block text-xs font-normal text-text-muted">{{ $payment->reference }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $payment->payment_date->format(df()) }}</td>
                                    <td class="px-4 py-3">{{ $payment->company?->name ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        @if ($payment->invoice)
                                            <a href="{{ route('invoices.show', $payment->invoice) }}" class="text-brand-primary hover:underline">#{{ $payment->invoice->label() }}</a>
                                        @else
                                            <span class="text-text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">{{ $payment->payment_method?->label() ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right">${{ number_format((float) $payment->total_due, 2) }}</td>
                                    <td class="px-4 py-3 text-right">${{ number_format((float) $payment->amount_received, 2) }}</td>
                                    <td class="px-4 py-3 text-right {{ $payment->shortfall() > 0 ? 'text-status-cancelled font-medium' : 'text-text-muted' }}">${{ number_format($payment->shortfall(), 2) }}</td>
                                    <td class="px-4 py-3"><x-status-badge :status="$payment->status" /></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-xs">
                                        @if ($payment->converted_at)
                                            <span class="text-status-paid">{{ $payment->conversion_date->format(df()) }}</span>
                                        @else
                                            <span class="text-text-muted">{{ __('Not yet') }}</span>
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
