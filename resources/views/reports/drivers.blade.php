@php($withCommission = auth()->user()->can('view-company-commission'))
<x-app-layout>
    <x-slot name="header">{{ __('Driver Earnings') }}</x-slot>

    <div class="space-y-6">
        <div>
            <h1 class="text-lg font-semibold text-text-primary dark:text-white">{{ __('Driver Earnings') }}</h1>
            <p class="text-sm text-text-muted">
                {{ __('What each driver hauled in the window. Cancelled loads are counted but add no miles or money.') }}
            </p>
        </div>

        <x-card>
            <x-reports.filter-form action="reports.drivers" :companies="$companies" :drivers="$drivers" :users="$users" :exportable="true" />
        </x-card>

        <div class="grid grid-cols-2 gap-4 {{ $withCommission ? 'lg:grid-cols-5' : 'lg:grid-cols-4' }}">
            <x-reports.stat :label="__('Loads')" :value="$totals['loads']" :hint="$totals['completed'].' '.__('delivered / paid').' · '.$totals['cancelled'].' '.__('cancelled')" />
            <x-reports.stat :label="__('Miles')" :value="number_format($totals['miles'])" />
            <x-reports.stat :label="__('Gross rate')" :value="$totals['rate']" :money="true" :hint="'$'.number_format($totals['rate_per_mile'], 2).' / mi'" />
            @if ($withCommission)
                <x-reports.stat :label="__('Company commission')" :value="$totals['commission']" :money="true" />
            @endif
            <x-reports.stat :label="__('Drivers')" :value="$rows->count()" />
        </div>

        <x-card :padded="false">
            @if ($rows->isEmpty())
                <x-empty-state icon="users" title="No driver activity in this window" />
            @else
                <div class="overflow-x-auto">
                    <table data-table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr>
                                <th class="px-4 py-3">Driver</th>
                                <th class="px-4 py-3">Company</th>
                                <th class="px-4 py-3 text-right">Loads</th>
                                <th class="px-4 py-3 text-right">Delivered / paid</th>
                                <th class="px-4 py-3 text-right">Cancelled</th>
                                <th class="px-4 py-3 text-right">Miles</th>
                                <th class="px-4 py-3 text-right">Gross rate</th>
                                <th class="px-4 py-3 text-right">Rate / mile</th>
                                @if ($withCommission)
                                    <th class="px-4 py-3 text-right">Commission</th>
                                @endif
                                <th class="px-4 py-3">Last pickup</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($rows as $row)
                                <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                    <td class="px-4 py-3">
                                        <span class="font-medium text-text-primary dark:text-white">{{ $row->driver_name }}</span>
                                        <span class="block text-xs text-text-muted">{{ __('Truck') }} {{ $row->truck_number }}</span>
                                    </td>
                                    <td class="px-4 py-3">{{ $row->company_name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right">{{ $row->loads_count }}</td>
                                    <td class="px-4 py-3 text-right">{{ $row->completed_count }}</td>
                                    <td class="px-4 py-3 text-right {{ $row->cancelled_count > 0 ? 'text-status-cancelled' : 'text-text-muted' }}">{{ $row->cancelled_count }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format((float) $row->miles_total) }}</td>
                                    <td class="px-4 py-3 text-right">${{ number_format((float) $row->rate_total, 2) }}</td>
                                    <td class="px-4 py-3 text-right">${{ number_format($row->rate_per_mile, 2) }}</td>
                                    @if ($withCommission)
                                        <td class="px-4 py-3 text-right">${{ number_format((float) $row->commission_total, 2) }}</td>
                                    @endif
                                    <td class="px-4 py-3 whitespace-nowrap text-xs text-text-muted">
                                        {{ $row->last_pickup ? \App\Support\Timezones::toUs(\Illuminate\Support\Carbon::parse($row->last_pickup, 'UTC'))->format('M j, Y') : '—' }}
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
