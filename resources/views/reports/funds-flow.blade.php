<x-app-layout>
    <x-slot name="header">{{ __('Funds flow') }}</x-slot>

    @php($money = fn ($v) => 'Rs '.number_format((float) $v, 2))
    <div class="space-y-6">
        <div>
            <h1 class="text-lg font-semibold text-text-primary dark:text-white">{{ __('Funds flow') }}</h1>
            <p class="text-sm text-text-muted">{{ __('Where the money came from and where it went. For every account: opening + money in − money out = closing, then each line traced to its document.') }}</p>
        </div>

        <x-card>
            <x-reports.filter-form action="reports.funds-flow" :exportable="true" :accounts="$accountOptions" :accounts-all="true" />
        </x-card>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
            <x-reports.stat :label="__('Opening')" :value="$totals['opening']" :money="true" currency="pkr" />
            <x-reports.stat :label="__('Money in')" :value="$totals['in']" :money="true" currency="pkr" />
            <x-reports.stat :label="__('Money out')" :value="$totals['out']" :money="true" currency="pkr" />
            <x-reports.stat :label="__('Closing')" :value="$totals['closing']" :money="true" currency="pkr" />
            <x-reports.stat :label="__('Net, excl. transfers')" :value="$totals['external_in'] - $totals['external_out']" :money="true" currency="pkr" :hint="__('Real income less real spending')" />
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <x-card :padded="false">
                <h2 class="border-b border-border px-5 py-3 font-bold text-text-primary dark:border-white/10 dark:text-white">{{ __('By account') }}</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted"><tr><th class="px-4 py-2">Account</th><th class="px-4 py-2 text-right">Opening</th><th class="px-4 py-2 text-right">In</th><th class="px-4 py-2 text-right">Out</th><th class="px-4 py-2 text-right">Closing</th></tr></thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($accounts as $a)
                                <tr><td class="px-4 py-2 font-medium text-text-primary dark:text-white">{{ $a['name'] }}<span class="block text-xs font-normal text-text-muted">{{ $a['type'] }}</span></td>
                                    <td class="px-4 py-2 text-right">{{ number_format($a['opening'], 2) }}</td>
                                    <td class="px-4 py-2 text-right text-status-paid">{{ number_format($a['in'], 2) }}</td>
                                    <td class="px-4 py-2 text-right text-status-cancelled">{{ number_format($a['out'], 2) }}</td>
                                    <td class="px-4 py-2 text-right font-semibold">{{ number_format($a['closing'], 2) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>

            <x-card :padded="false">
                <h2 class="border-b border-border px-5 py-3 font-bold text-text-primary dark:border-white/10 dark:text-white">{{ __('By source') }}</h2>
                @forelse ($by_source as $s)
                    <div class="flex items-center gap-3 border-b border-border/60 px-5 py-2.5 text-sm last:border-0 dark:border-white/5">
                        <span class="flex-1 font-medium text-text-primary dark:text-white">{{ $s['label'] }}</span>
                        <span class="w-32 text-right text-status-paid">{{ $s['in'] > 0 ? '+ '.number_format($s['in'], 2) : '' }}</span>
                        <span class="w-32 text-right text-status-cancelled">{{ $s['out'] > 0 ? '− '.number_format($s['out'], 2) : '' }}</span>
                    </div>
                @empty
                    <p class="px-5 py-4 text-sm text-text-muted">{{ __('No movements in this period.') }}</p>
                @endforelse
            </x-card>
        </div>

        <x-card :padded="false">
            <h2 class="border-b border-border px-5 py-3 font-bold text-text-primary dark:border-white/10 dark:text-white">{{ __('Every movement') }}@if ($truncated) <span class="text-xs font-normal text-text-muted">· {{ __('latest 300 shown, export for all') }}</span>@endif</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                    <thead class="text-left text-xs uppercase tracking-wider text-text-muted"><tr><th class="px-4 py-2">Date</th><th class="px-4 py-2">Account</th><th class="px-4 py-2">What</th><th class="px-4 py-2">Party</th><th class="px-4 py-2 text-right">In</th><th class="px-4 py-2 text-right">Out</th></tr></thead>
                    <tbody class="divide-y divide-border/60 dark:divide-white/5">
                        @forelse ($rows as $t)
                            <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                <td class="whitespace-nowrap px-4 py-2">{{ $t->occurred_on?->format('M j, Y') }}</td>
                                <td class="px-4 py-2">{{ $t->account?->name }}</td>
                                <td class="px-4 py-2">@if ($t->flow['url'])<a href="{{ $t->flow['url'] }}" class="font-medium hover:text-brand-primary">{{ $t->flow['title'] }}</a>@else<span class="font-medium">{{ $t->flow['title'] }}</span>@endif</td>
                                <td class="px-4 py-2 text-text-muted">{{ $t->flow['party'] ?: '—' }}</td>
                                <td class="px-4 py-2 text-right text-status-paid">{{ $t->type->sign() > 0 ? number_format((float) $t->amount, 2) : '' }}</td>
                                <td class="px-4 py-2 text-right text-status-cancelled">{{ $t->type->sign() < 0 ? number_format((float) $t->amount, 2) : '' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-text-muted">{{ __('No movements in this period.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</x-app-layout>
