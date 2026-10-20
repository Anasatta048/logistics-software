<x-app-layout>
    <x-slot name="header">{{ __('Broker Performance') }}</x-slot>

    <div class="space-y-6">
        <div>
            <h1 class="text-lg font-semibold text-text-primary dark:text-white">{{ __('Broker Performance') }}</h1>
            <p class="text-sm text-text-muted">
                {{ __('Per broker, over the loads picked up in the window: volume, what the paid loads brought in, and the share of loads that were cancelled.') }}
            </p>
        </div>

        <x-card>
            <x-reports.filter-form action="reports.brokers" :exportable="true" :brokers="$brokers" />
        </x-card>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
            <x-reports.stat :label="__('Loads')" :value="$totals['loads']" />
            <x-reports.stat :label="__('Paid revenue')" :value="$totals['revenue']" :money="true" />
            <x-reports.stat :label="__('Cancelled')" :value="$totals['cancelled']" :tone="$totals['cancelled'] > 0 ? 'bad' : null"
                :hint="$totals['loads'] > 0 ? number_format($totals['cancelled'] / $totals['loads'] * 100, 1).'% '.__('of loads') : null" />
        </div>

        <x-card :padded="false">
            @if ($rows->isEmpty())
                <x-empty-state icon="building" title="No broker activity in this window" />
            @else
                <div class="overflow-x-auto">
                    <table data-table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr>
                                <th class="px-4 py-3">Broker</th>
                                <th class="px-4 py-3 text-right">Loads</th>
                                <th class="px-4 py-3 text-right">Miles</th>
                                <th class="px-4 py-3 text-right">Paid revenue</th>
                                <th class="px-4 py-3 text-right">Cancelled</th>
                                <th class="px-4 py-3 text-right">Cancellation rate</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($rows as $row)
                                <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 font-medium text-text-primary dark:text-white">{{ $row->company_name }}</td>
                                    <td class="px-4 py-3 text-right">{{ $row->loads_count }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format((float) $row->miles_total) }}</td>
                                    <td class="px-4 py-3 text-right">${{ number_format((float) $row->revenue_total, 2) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $row->cancelled_count }}</td>
                                    <td class="px-4 py-3 text-right {{ $row->cancellation_rate >= 20 ? 'text-status-cancelled font-medium' : '' }}">{{ $row->cancellation_rate }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
</x-app-layout>
