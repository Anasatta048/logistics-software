<x-app-layout>
    <x-slot name="header">{{ __('Settlement Statement') }}</x-slot>

    <div class="space-y-6">
        <div>
            <h1 class="text-lg font-semibold text-text-primary dark:text-white">{{ __('Settlement Statement') }}</h1>
            <p class="text-sm text-text-muted">
                {{ __('Pick a carrier company or a driver and a date range (by pickup). The statement lists each finished load, the commission kept and what they take home, ready to download as PDF.') }}
            </p>
        </div>

        <x-card>
            <x-reports.filter-form action="reports.settlements" :exportable="true" :companies="$companies" :drivers="$drivers"
                :statuses="['paid' => __('Paid loads only'), 'delivered_paid' => __('Delivered and paid')]" status-label="Loads included" />
            <p class="mt-3 text-xs text-text-muted">{{ __('Choose a company or a driver. If you choose both, the driver is used.') }}</p>
        </x-card>

        @if (! $subject)
            <x-card><x-empty-state icon="file-text" title="Choose who the statement is for" description="Select a company or a driver above." /></x-card>
        @else
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <x-reports.stat :label="__('Loads')" :value="$totals['count']" />
                <x-reports.stat :label="__('Gross')" :value="$totals['gross']" :money="true" />
                <x-reports.stat :label="__('Commission kept')" :value="$totals['commission']" :money="true" />
                <x-reports.stat :label="__('Net to carrier')" :value="$totals['net']" :money="true" tone="good" />
            </div>

            <x-card :padded="false">
                <div class="border-b border-border px-4 py-3 text-sm font-extrabold text-text-primary dark:border-white/10 dark:text-white">{{ $subject->name }}
                    <span class="font-normal text-text-muted">{{ $subject instanceof \App\Models\Driver ? ($subject->truck_number ? '· '.$subject->truck_number : '') : ($subject->mc_number ? '· '.$subject->mc_number : '') }}</span></div>
                @if ($rows->isEmpty())
                    <x-empty-state icon="truck" title="No finished loads in this window" description="Try a wider date range, or include delivered loads." />
                @else
                    <div class="overflow-x-auto">
                        <table data-table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                            <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                                <tr><th class="px-4 py-3">Load</th><th class="px-4 py-3">Pickup</th><th class="px-4 py-3">Route</th><th class="px-4 py-3 text-right">Miles</th><th class="px-4 py-3 text-right">Gross</th><th class="px-4 py-3 text-right">Commission</th><th class="px-4 py-3 text-right">Net</th></tr>
                            </thead>
                            <tbody class="divide-y divide-border/60 dark:divide-white/5">
                                @foreach ($rows as $load)
                                    <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                        <td class="px-4 py-3 font-medium"><a href="{{ route('loads.show', $load) }}" class="text-brand-primary hover:underline">{{ $load->reference_number }}</a></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-text-muted">{{ \App\Support\Timezones::toUs($load->pickup_at)?->format(df()) }}</td>
                                        <td class="px-4 py-3 text-xs">{{ \Illuminate\Support\Str::limit($load->origin_address, 26) }}<br><span class="text-text-muted">&rarr; {{ \Illuminate\Support\Str::limit($load->destination_address, 26) }}</span></td>
                                        <td class="px-4 py-3 text-right">{{ number_format((float) $load->total_miles) }}</td>
                                        <td class="px-4 py-3 text-right">${{ number_format((float) $load->rate, 2) }}</td>
                                        <td class="px-4 py-3 text-right text-text-muted">${{ number_format((float) $load->company_commission_amount, 2) }}</td>
                                        <td class="px-4 py-3 text-right font-semibold">${{ number_format((float) $load->rate - (float) $load->company_commission_amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>
        @endif
    </div>
</x-app-layout>
