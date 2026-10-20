<x-app-layout>
    <x-slot name="header">{{ __('Expense report') }}</x-slot>

    @php
        $money = fn ($v) => 'Rs '.number_format((float) $v, 2);
        $sections = [
            ['key' => 'category', 'title' => __('By category'), 'col' => __('Category'), 'rows' => array_values($rows)],
            ['key' => 'month', 'title' => __('By month'), 'col' => __('Month'), 'rows' => $months],
            ['key' => 'account', 'title' => __('Paid from (account)'), 'col' => __('Account'), 'rows' => $accounts],
            ['key' => 'method', 'title' => __('By payment method'), 'col' => __('Method'), 'rows' => $methods],
            ['key' => 'payee', 'title' => __('Top payees'), 'col' => __('Paid to'), 'rows' => $payees],
        ];
        $top = collect($rows)->sortByDesc('total')->first();
        $maxMonth = max(1, collect($months)->max('total'));
    @endphp

    <div class="space-y-6">
        <div>
            <h1 class="text-lg font-semibold text-text-primary dark:text-white">{{ __('Expense report') }}</h1>
            <p class="text-sm text-text-muted">{{ __('Where the money went: by category, month, account, method and payee. Filter, then export any table.') }}</p>
        </div>

        <x-card>
            <x-reports.filter-form action="reports.expenses" :accounts="$accounts" :accounts-all="true" :categories="$categories" />
        </x-card>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-reports.stat :label="__('Total spent')" :value="$total" :money="true" :hint="$count.' '.__('entries')" />
            <x-reports.stat :label="__('Average per entry')" :value="$average" :money="true" />
            <x-reports.stat :label="__('Average per month')" :value="$monthlyAverage" :money="true" />
            <x-reports.stat :label="__('Biggest category')" :value="$top && $top['total'] > 0 ? $top['label'] : '—'"
                :hint="$top && $top['total'] > 0 ? $money($top['total']).' · '.$top['share'].'%' : null" />
        </div>

        {{-- Monthly trend --}}
        @if (collect($months)->sum('total') > 0)
            <x-card>
                <h2 class="font-bold text-text-primary dark:text-white">{{ __('Monthly trend') }}</h2>
                <div class="mt-4 flex h-44 items-end gap-2">
                    @foreach ($months as $m)
                        <div class="flex min-w-0 flex-1 flex-col items-center justify-end gap-1" title="{{ $m['label'] }}: {{ $money($m['total']) }}">
                            <span class="text-[10px] text-text-muted">{{ number_format($m['total'] / 1000, 0) }}k</span>
                            <div class="w-full bg-brand-primary" style="height: {{ max(2, $m['total'] / $maxMonth * 100) }}%"></div>
                            <span class="truncate text-[10px] text-text-muted">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $m['label'])->format('M y') }}</span>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @endif

        @foreach ($sections as $section)
            @continue(count($section['rows']) === 0)
            <x-card :padded="false">
                <div class="flex items-center justify-between border-b border-border px-5 py-3 dark:border-white/10">
                    <h2 class="font-bold text-text-primary dark:text-white">{{ $section['title'] }}</h2>
                    <div class="flex gap-2">
                        <a data-no-loader href="{{ route('reports.expenses', array_merge(request()->query(), ['export' => 'csv', 'section' => $section['key']])) }}" class="btn-ghost btn-sm"><x-icon name="download" :size="14" /> CSV</a>
                        <a data-no-loader href="{{ route('reports.expenses', array_merge(request()->query(), ['export' => 'pdf', 'section' => $section['key']])) }}" class="btn-ghost btn-sm"><x-icon name="printer" :size="14" /> PDF</a>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr>
                                <th class="px-4 py-3">{{ $section['col'] }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Entries') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Total') }}</th>
                                <th class="w-1/3 px-4 py-3">{{ __('Share') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($section['rows'] as $row)
                                <tr class="hover:bg-bg/60 dark:hover:bg-white/5 {{ $row['count'] === 0 ? 'text-text-muted' : '' }}">
                                    <td class="px-4 py-3 font-medium {{ $row['count'] === 0 ? '' : 'text-text-primary dark:text-white' }}">{{ $section['key'] === 'method' ? str($row['label'])->headline() : $row['label'] }}</td>
                                    <td class="px-4 py-3 text-right">{{ $row['count'] }}</td>
                                    <td class="px-4 py-3 text-right">{{ $money($row['total']) }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="h-2 flex-1 bg-border/60 dark:bg-white/10"><div class="h-2 bg-brand-primary" style="width: {{ min(100, $row['share']) }}%"></div></div>
                                            <span class="w-12 text-right text-xs">{{ $row['share'] }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        @endforeach
    </div>
</x-app-layout>
