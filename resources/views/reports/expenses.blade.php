<x-app-layout>
    <x-slot name="header">{{ __('Expenses') }}</x-slot>

    <div class="space-y-6">
        <div>
            <h1 class="text-lg font-semibold text-text-primary dark:text-white">{{ __('Expenses') }}</h1>
            <p class="text-sm text-text-muted">
                {{ __('What was spent in the window, by category, with each category\'s share of the total.') }}
            </p>
        </div>

        <x-card>
            <x-reports.filter-form action="reports.expenses" :exportable="true" />
        </x-card>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
            <x-reports.stat :label="__('Total spent')" :value="$total" :money="true" :hint="$count.' '.__('entries')" />
            @php($top = collect($rows)->sortByDesc('total')->first())
            <x-reports.stat :label="__('Biggest category')" :value="$top && $top['total'] > 0 ? $top['label'] : '—'"
                :hint="$top && $top['total'] > 0 ? '$'.number_format($top['total'], 2).' · '.$top['share'].'%' : null" />
            <x-reports.stat :label="__('Categories used')" :value="collect($rows)->where('count', '>', 0)->count().' / '.count($rows)" />
        </div>

        <x-card :padded="false">
            <div class="overflow-x-auto">
                <table data-table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                    <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                        <tr>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3 text-right">Entries</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 w-1/3">Share</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60 dark:divide-white/5">
                        @foreach ($rows as $row)
                            <tr class="hover:bg-bg/60 dark:hover:bg-white/5 {{ $row['count'] === 0 ? 'text-text-muted' : '' }}">
                                <td class="px-4 py-3 font-medium {{ $row['count'] === 0 ? '' : 'text-text-primary dark:text-white' }}">{{ $row['label'] }}</td>
                                <td class="px-4 py-3 text-right">{{ $row['count'] }}</td>
                                <td class="px-4 py-3 text-right">${{ number_format($row['total'], 2) }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="h-2 flex-1 rounded-full bg-border/60 dark:bg-white/10">
                                            <div class="h-2 rounded-full bg-brand-primary" style="width: {{ min(100, $row['share']) }}%"></div>
                                        </div>
                                        <span class="w-12 text-right text-xs">{{ $row['share'] }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-border/60 text-sm font-semibold dark:border-white/10">
                        <tr>
                            <td class="px-4 py-3">{{ __('Total') }}</td>
                            <td class="px-4 py-3 text-right">{{ $count }}</td>
                            <td class="px-4 py-3 text-right">${{ number_format($total, 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-card>
    </div>
</x-app-layout>
