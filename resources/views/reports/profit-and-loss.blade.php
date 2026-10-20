<x-app-layout>
    <x-slot name="header">{{ __('Profit & Loss') }}</x-slot>

    <div class="space-y-6">
        <div>
            <h1 class="text-lg font-semibold text-text-primary dark:text-white">{{ __('Profit & Loss') }}</h1>
            <p class="text-sm text-text-muted">
                {{ __('Company commission billed on Paid loads, minus recorded expenses. Dispatcher commission is a staff cost, tracked separately.') }}
            </p>
        </div>

        <x-card>
            <x-reports.filter-form action="reports.profit-and-loss" :exportable="true" />
        </x-card>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ([
                'Commission Billed' => $totals['revenue'],
                'Gross Rate' => $totals['gross_rate'],
                'Expenses' => $totals['expenses'],
                'Net Profit' => $totals['net_profit'],
            ] as $label => $amount)
                <x-card class="px-4 py-3">
                    <div class="text-xs uppercase tracking-wider text-text-muted">{{ $label }}</div>
                    <div class="text-xl font-semibold {{ $amount < 0 ? 'text-status-cancelled' : 'text-text-primary dark:text-white' }}">
                        ${{ number_format($amount, 2) }}
                    </div>
                </x-card>
            @endforeach
        </div>

        <p class="text-sm text-text-muted">
            {{ __('Net margin: :pct%', ['pct' => $totals['margin_percentage']]) }}
        </p>
    </div>
</x-app-layout>
