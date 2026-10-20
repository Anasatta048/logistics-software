<x-app-layout>
    <x-slot name="header">{{ __('PKR Conversions') }}</x-slot>

    <div class="space-y-6">
        <div>
            <h1 class="text-lg font-semibold text-text-primary dark:text-white">{{ __('PKR Conversions') }}</h1>
            <p class="text-sm text-text-muted">
                {{ __('Every USD received that was sold for PKR in the window: the rate used, the tax withheld and the account the net amount landed in. Amounts in PKR unless marked USD.') }}
            </p>
        </div>

        <x-card>
            <x-reports.filter-form action="reports.conversions" :companies="$companies" :exportable="true" />
        </x-card>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
            <x-reports.stat :label="__('USD converted')" :value="$totals['usd']" :money="true" />
            <x-reports.stat :label="__('Average rate')" :value="number_format($totals['average_rate'], 4)" hint="PKR / USD" />
            <x-reports.stat :label="__('PKR gross')" :value="number_format($totals['pkr_gross'], 2)" />
            <x-reports.stat :label="__('Tax')" :value="number_format($totals['tax'], 2)" />
            <x-reports.stat :label="__('PKR net')" :value="number_format($totals['pkr_net'], 2)" />
        </div>

        <x-card :padded="false">
            @if ($rows->isEmpty())
                <x-empty-state icon="wallet" title="No conversions in this window" />
            @else
                <div class="overflow-x-auto">
                    <table data-table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Company</th>
                                <th class="px-4 py-3 text-right">USD</th>
                                <th class="px-4 py-3 text-right">Rate</th>
                                <th class="px-4 py-3 text-right">PKR gross</th>
                                <th class="px-4 py-3 text-right">Tax</th>
                                <th class="px-4 py-3 text-right">PKR net</th>
                                <th class="px-4 py-3">Deposited to</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($rows as $conversion)
                                <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <a href="{{ route('company-payments.show', $conversion) }}" class="hover:underline">{{ $conversion->conversion_date->format('M j, Y') }}</a>
                                    </td>
                                    <td class="px-4 py-3">{{ $conversion->company?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right">${{ number_format((float) $conversion->converted_usd, 2) }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format((float) $conversion->conversion_rate, 4) }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format((float) $conversion->pkr_gross, 2) }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format((float) $conversion->tax_amount, 2) }}</td>
                                    <td class="px-4 py-3 text-right font-medium">{{ number_format((float) $conversion->pkr_net, 2) }}</td>
                                    <td class="px-4 py-3">{{ $conversion->depositAccount?->name ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
</x-app-layout>
