<x-app-layout>
    <x-slot name="header">{{ __('Dispatcher Ledger') }}</x-slot>

    <div class="space-y-6">
        @if (session('success'))
            <x-alert variant="success">{{ session('success') }}</x-alert>
        @endif

        <div class="form-page-head !mb-0 flex-wrap">
            <span class="form-page-icon"><x-icon name="percent" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ $dispatcher->name }}</h1>
                <p class="text-sm text-text-muted">{{ __('Commission account in USD · paid out in PKR') }} · {{ $dispatcher->commissionLabel() }}</p>
            </div>
            <div class="flex items-center gap-3">
                @can('pay', App\Models\Commission::class)
                    <x-button variant="primary" as="a" href="{{ route('commissions.payouts.create', ['dispatcher_id' => $dispatcher->id]) }}">
                        <x-icon name="wallet" :size="16" /> {{ __('Record payout') }}
                    </x-button>
                @endcan
                <a href="{{ route('commissions.index') }}" class="text-sm text-text-muted hover:underline">{{ __('Back to Commissions') }}</a>
            </div>
        </div>

        @include('ledger.summary', ['kind' => 'dispatcher', 'position' => $position])

        @include('ledger.open-items', ['kind' => 'dispatcher', 'position' => $position])

        <x-card :padded="false">
            <div class="border-b border-border/60 px-4 py-3 dark:border-white/10">
                <h3 class="text-sm font-semibold text-text-primary dark:text-white">{{ __('Recent payouts') }}</h3>
            </div>
            @if ($payouts->isEmpty())
                <p class="px-4 py-6 text-sm text-text-muted">{{ __('Nothing has been paid out yet.') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr>
                                <th class="px-4 py-3">{{ __('Date') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('USD') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Rate') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Paid (PKR)') }}</th>
                                <th class="px-4 py-3">{{ __('Method') }}</th>
                                <th class="px-4 py-3">{{ __('From account') }}</th>
                                <th class="px-4 py-3">{{ __('Reference') }}</th>
                                <th class="px-4 py-3">{{ __('By') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($payouts as $payout)
                                <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $payout->paid_at->format('M j, Y') }}</td>
                                    <td class="px-4 py-3 text-right font-medium">${{ number_format((float) $payout->usd_amount, 2) }}</td>
                                    <td class="px-4 py-3 text-right text-text-muted">{{ rtrim(rtrim((string) $payout->conversion_rate, '0'), '.') }}</td>
                                    <td class="px-4 py-3 text-right">Rs {{ number_format((float) $payout->pkr_amount, 2) }}</td>
                                    <td class="px-4 py-3">{{ $payout->payment_method?->label() ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $payout->paymentAccount?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-text-muted">{{ $payout->reference ?: '—' }}</td>
                                    <td class="px-4 py-3 text-text-muted">{{ $payout->paidByUser?->name ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        @include('ledger.statement', ['kind' => 'dispatcher', 'statement' => $statement, 'action' => route('commissions.ledger', $dispatcher)])
    </div>
</x-app-layout>
