<x-app-layout>
    <x-slot name="header">{{ __('Broker Aging & Delays') }}</x-slot>

    <div class="space-y-6">
        <div>
            <h1 class="text-lg font-semibold text-text-primary dark:text-white">{{ __('Broker Aging & Delays') }}</h1>
            <p class="text-sm text-text-muted">
                {{ __('Money brokers still owe on delivered loads, by how many days ago the load was delivered, and how long each broker takes to pay. The date filter applies to the delivery date.') }}
            </p>
        </div>

        <x-card>
            <x-reports.filter-form action="reports.broker-aging" :exportable="true" :brokers="$brokers" />
        </x-card>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-reports.stat :label="__('Unpaid by brokers')" :value="$totals['open_total']" :money="true" :hint="$totals['open_count'].' '.__('loads')" />
            <x-reports.stat :label="__('Unpaid over :days days', ['days' => \App\Services\Report\ReportService::SLOW_PAY_DAYS])" :value="$totals['over_30']" :money="true" :tone="$totals['over_30'] > 0 ? 'bad' : null" />
            <x-reports.stat :label="__('Avg days to pay')" :value="$totals['avg_days_to_pay']" :hint="__('delivery to paid')" />
            <x-reports.stat :label="__('Brokers owing')" :value="$rows->where('open_count', '>', 0)->count()" />
        </div>

        <x-card :padded="false">
            @if ($rows->isEmpty())
                <x-empty-state icon="building" title="No delivered loads in this window" description="Loads appear here once they are marked delivered." />
            @else
                <div class="overflow-x-auto">
                    <table data-table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr>
                                <th class="px-4 py-3">Broker</th>
                                <th class="px-4 py-3 text-right">Unpaid</th>
                                @foreach ($bands as $band)<th class="px-4 py-3 text-right">{{ $band }} d</th>@endforeach
                                <th class="px-4 py-3 text-right">Oldest</th>
                                <th class="px-4 py-3 text-right">Avg days to pay</th>
                                <th class="px-4 py-3 text-right">Paid late</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($rows as $row)
                                <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 font-medium text-text-primary dark:text-white">{{ $row->company_name }}</td>
                                    <td class="px-4 py-3 text-right font-semibold">${{ number_format($row->open_total, 2) }}<span class="block text-xs font-normal text-text-muted">{{ $row->open_count }} {{ __('loads') }}</span></td>
                                    @foreach ($bands as $band)
                                        @php($amount = $row->{'band_'.$band})
                                        <td class="px-4 py-3 text-right {{ $amount > 0 && in_array($band, ['46-60', '60+'], true) ? 'font-semibold text-status-cancelled' : ($amount > 0 && $band === '31-45' ? 'text-warning' : 'text-text-muted') }}">{{ $amount > 0 ? '$'.number_format($amount, 2) : '—' }}</td>
                                    @endforeach
                                    <td class="px-4 py-3 text-right {{ $row->oldest_days > 45 ? 'font-semibold text-status-cancelled' : '' }}">{{ $row->open_count ? $row->oldest_days.' d' : '—' }}</td>
                                    <td class="px-4 py-3 text-right {{ ($row->avg_days_to_pay ?? 0) > \App\Services\Report\ReportService::SLOW_PAY_DAYS ? 'font-semibold text-status-cancelled' : '' }}">{{ $row->avg_days_to_pay !== null ? $row->avg_days_to_pay.' d' : '—' }}</td>
                                    <td class="px-4 py-3 text-right">{{ $row->slow_pct !== null ? $row->slow_pct.'%' : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        @if ($open->isNotEmpty())
            <div>
                <h2 class="mb-2 text-sm font-extrabold uppercase tracking-wider text-text-muted">{{ __('Oldest unpaid loads') }}</h2>
                <x-card :padded="false">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                            <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                                <tr><th class="px-4 py-3">Load</th><th class="px-4 py-3">Broker</th><th class="px-4 py-3">Delivered</th><th class="px-4 py-3 text-right">Age</th><th class="px-4 py-3 text-right">Amount</th></tr>
                            </thead>
                            <tbody class="divide-y divide-border/60 dark:divide-white/5">
                                @foreach ($open->take(25) as $load)
                                    <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                        <td class="px-4 py-3 font-medium"><a href="{{ route('loads.show', $load->id) }}" class="text-brand-primary hover:underline">{{ $load->reference_number }}</a></td>
                                        <td class="px-4 py-3">{{ $load->company_name }}</td>
                                        <td class="px-4 py-3 text-text-muted">{{ $load->delivered->format(df()) }}</td>
                                        <td class="px-4 py-3 text-right {{ $load->age_days > 45 ? 'font-semibold text-status-cancelled' : ($load->age_days > 30 ? 'text-warning' : '') }}">{{ $load->age_days }} d</td>
                                        <td class="px-4 py-3 text-right">${{ number_format((float) $load->rate, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>
        @endif
    </div>
</x-app-layout>
