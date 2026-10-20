<x-app-layout>
    <x-slot name="header">{{ __('Profit & Loss') }}</x-slot>

    @php
        $pkr = fn ($v) => cur_local().' '.number_format((float) $v, 2);
        $usd = fn ($v) => cur_foreign().number_format((float) $v, 2);
    @endphp
    <div class="space-y-6">
        <div>
            <h1 class="text-lg font-semibold text-text-primary dark:text-white">{{ __('Profit & Loss') }}</h1>
            <p class="text-sm text-text-muted">
                {{ __('Cash basis, in PKR: what actually reached your accounts after USD was converted, less office expenses and dispatcher commission paid out. Client fees billed (USD) are shown for context.') }}
            </p>
        </div>

        <x-card>
            <x-reports.filter-form action="reports.profit-and-loss" :exportable="true" />
        </x-card>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-reports.stat :label="__('Fees billed (USD)')" :value="$totals['revenue']" :money="true" :hint="__('On Paid loads, gross freight').' '.$usd($totals['gross_rate'])" />
            <x-reports.stat :label="__('Converted to PKR')" :value="$totals['converted_pkr']" :money="true" currency="pkr" :hint="$usd($totals['converted_usd']).' '.__('after tax Rs').' '.number_format($totals['tax_pkr'], 2)" />
            <x-reports.stat :label="__('Office expenses')" :value="$totals['expenses']" :money="true" currency="pkr" />
            <x-reports.stat :label="__('Dispatcher commission paid')" :value="$totals['payouts_pkr']" :money="true" currency="pkr" />
        </div>

        <x-card class="px-5 py-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-text-muted">{{ __('Net profit') }}</p>
                    <p class="text-3xl font-extrabold {{ $totals['net_profit'] < 0 ? 'text-status-cancelled' : 'text-status-paid' }}">{{ $pkr($totals['net_profit']) }}</p>
                    <p class="mt-1 text-xs text-text-muted">{{ $pkr($totals['converted_pkr']) }} − {{ $pkr($totals['expenses']) }} − {{ $pkr($totals['payouts_pkr']) }}</p>
                </div>
                @if ($totals['converted_pkr'] > 0)
                    <div class="text-right">
                        <p class="text-xs font-semibold uppercase tracking-wider text-text-muted">{{ __('Net margin') }}</p>
                        <p class="text-2xl font-bold text-text-primary dark:text-white">{{ $totals['margin_percentage'] }}%</p>
                    </div>
                @endif
            </div>
        </x-card>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-card class="px-4 py-3">
                <p class="text-xs uppercase tracking-wider text-text-muted">{{ __('Received in USD, not yet converted') }}</p>
                <p class="text-xl font-semibold text-brand-gold">{{ $usd($totals['unconverted_usd']) }}</p>
                <p class="text-xs text-text-muted">{{ __('Turns into income once converted to PKR.') }}</p>
            </x-card>
            <x-card class="px-4 py-3">
                <p class="text-xs uppercase tracking-wider text-text-muted">{{ __('Dispatcher commission still owed (USD)') }}</p>
                <p class="text-xl font-semibold text-brand-gold">{{ $usd($totals['payable_usd']) }}</p>
                <p class="text-xs text-text-muted">{{ __('Earned but not paid out yet.') }}</p>
            </x-card>
        </div>
    </div>
</x-app-layout>
