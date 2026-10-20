@php($withCommission = auth()->user()->can('view-company-commission'))
<x-app-layout>
    <x-slot name="header">{{ __('Load Register') }}</x-slot>

    <div class="space-y-6">
        <div>
            <h1 class="text-lg font-semibold text-text-primary dark:text-white">{{ __('Load Register') }}</h1>
            <p class="text-sm text-text-muted">
                {{ __('Every load picked up in the window, by status, company, broker, driver and dispatcher. Cancelled loads stay in with who cancelled them and why. Dates are :zone days.', ['zone' => \App\Support\Timezones::usLabel()]) }}
            </p>
        </div>

        <x-card>
            <x-reports.filter-form action="reports.loads" :statuses="collect($statuses)->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()"
                :companies="$companies" :brokers="$brokers" :drivers="$drivers" :users="$users" :exportable="true" />
        </x-card>

        <div class="grid grid-cols-2 gap-4 {{ $withCommission ? 'lg:grid-cols-5' : 'lg:grid-cols-4' }}">
            <x-reports.stat :label="__('Loads')" :value="$totals['count']" :hint="$totals['cancelled'].' '.__('cancelled')" />
            <x-reports.stat :label="__('Gross rate')" :value="$totals['rate']" :money="true" :hint="__('excl. cancelled')" />
            <x-reports.stat :label="__('Miles')" :value="number_format($totals['miles'])" :hint="'$'.number_format($totals['rate_per_mile'], 2).' / mi'" />
            @if ($withCommission)
                <x-reports.stat :label="__('Company commission')" :value="$totals['commission']" :money="true" />
            @endif
            <x-reports.stat :label="__('Avg. rate / load')" :value="($totals['count'] - $totals['cancelled']) > 0 ? '$'.number_format($totals['rate'] / ($totals['count'] - $totals['cancelled']), 2) : '—'" />
        </div>

        <x-card :padded="false">
            @if ($rows->isEmpty())
                <x-empty-state icon="truck" title="No loads match this filter" />
            @else
                <div class="overflow-x-auto">
                    <table data-table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr>
                                <th class="px-4 py-3">Reference</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Company</th>
                                <th class="px-4 py-3">Broker</th>
                                <th class="px-4 py-3">Driver</th>
                                <th class="px-4 py-3">Dispatcher</th>
                                <th class="px-4 py-3">Pickup</th>
                                <th class="px-4 py-3">Route</th>
                                <th class="px-4 py-3 text-right">Miles</th>
                                <th class="px-4 py-3 text-right">Rate</th>
                                @if ($withCommission)
                                    <th class="px-4 py-3 text-right">Commission</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($rows as $load)
                                <tr class="align-top hover:bg-bg/60 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 font-medium text-text-primary dark:text-white whitespace-nowrap">
                                        <a href="{{ route('loads.edit', $load) }}" class="hover:underline">{{ $load->reference_number }}</a>
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-status-badge :status="$load->status" />
                                        @if ($load->status === \App\Enums\LoadStatus::Cancelled)
                                            <p class="mt-1 max-w-[16rem] text-xs text-text-muted">
                                                {{ $load->cancellation_reason ?: __('No reason given') }}
                                                @if ($load->cancelledBy)
                                                    — {{ $load->cancelledBy->name }}
                                                @endif
                                            </p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">{{ $load->company?->name ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $load->broker?->company_name ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $load->driver?->name ?? $load->driver_name ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $load->dispatcher?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap"><x-datetime :value="$load->pickup_at" /></td>
                                    <td class="px-4 py-3 text-xs text-text-muted">
                                        <span class="block max-w-[14rem] truncate" title="{{ $load->origin_address }}">{{ $load->origin_address }}</span>
                                        <span class="block max-w-[14rem] truncate" title="{{ $load->destination_address }}">→ {{ $load->destination_address }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">{{ number_format((float) $load->total_miles) }}</td>
                                    <td class="px-4 py-3 text-right">${{ number_format((float) $load->rate, 2) }}</td>
                                    @if ($withCommission)
                                        <td class="px-4 py-3 text-right">${{ number_format((float) $load->company_commission_amount, 2) }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
</x-app-layout>
