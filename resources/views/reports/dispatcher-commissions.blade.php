<x-app-layout>
    <x-slot name="header">{{ __('Dispatcher Commissions') }}</x-slot>

    <div class="space-y-6">
        <div>
            <h1 class="text-lg font-semibold text-text-primary dark:text-white">{{ __('Dispatcher Commissions') }}</h1>
            <p class="text-sm text-text-muted">
                {{ __('Commission earned on loads calculated in the window, split into already paid and still payable. A cancelled load\'s commission is reversed and shown apart. Payouts are the cash actually paid out in the window.') }}
            </p>
        </div>

        <x-card>
            <x-reports.filter-form action="reports.dispatcher-commissions" :users="$users" :exportable="true" />
        </x-card>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
            <x-reports.stat :label="__('Loads')" :value="$totals['loads']" />
            <x-reports.stat :label="__('Commission earned')" :value="$totals['earned']" :money="true" />
            <x-reports.stat :label="__('Already paid')" :value="$totals['paid']" :money="true" />
            <x-reports.stat :label="__('Still payable')" :value="$totals['payable']" :money="true" :tone="$totals['payable'] > 0 ? 'bad' : 'good'" />
            <x-reports.stat :label="__('Payouts made')" :value="$totals['payouts_usd']" :money="true" :hint="'PKR '.number_format($totals['payouts_pkr'], 2)" />
        </div>

        <x-card :padded="false">
            @if ($rows->isEmpty())
                <x-empty-state icon="chart-bar" title="No commission activity in this window" />
            @else
                <div class="overflow-x-auto">
                    <table data-table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr>
                                <th class="px-4 py-3">Dispatcher</th>
                                <th class="px-4 py-3 text-right">Loads</th>
                                <th class="px-4 py-3 text-right">Margin</th>
                                <th class="px-4 py-3 text-right">Earned</th>
                                <th class="px-4 py-3 text-right">Already paid</th>
                                <th class="px-4 py-3 text-right">Still payable</th>
                                <th class="px-4 py-3 text-right">Reversed</th>
                                <th class="px-4 py-3 text-right">Payouts (USD)</th>
                                <th class="px-4 py-3 text-right">Payouts (PKR)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($rows as $row)
                                <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 font-medium text-text-primary dark:text-white">{{ $row->user_name }}</td>
                                    <td class="px-4 py-3 text-right">{{ $row->loads_count }}</td>
                                    <td class="px-4 py-3 text-right">${{ number_format($row->margin_total, 2) }}</td>
                                    <td class="px-4 py-3 text-right">${{ number_format($row->earned_total, 2) }}</td>
                                    <td class="px-4 py-3 text-right">${{ number_format($row->paid_total, 2) }}</td>
                                    <td class="px-4 py-3 text-right {{ $row->payable_total > 0 ? 'font-medium text-status-cancelled' : 'text-text-muted' }}">${{ number_format($row->payable_total, 2) }}</td>
                                    <td class="px-4 py-3 text-right text-text-muted">${{ number_format($row->reversed_total, 2) }}</td>
                                    <td class="px-4 py-3 text-right">${{ number_format($row->payouts_usd, 2) }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format($row->payouts_pkr, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
</x-app-layout>
