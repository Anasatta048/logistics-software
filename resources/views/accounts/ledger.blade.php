<x-app-layout>
    <x-slot name="header">{{ __('Ledger') }} — {{ $account->name }}</x-slot>

    @php($money = fn ($v) => 'Rs '.number_format((float) $v, 2))
    <div class="space-y-6">
        <div class="form-page-head !mb-0 flex-wrap">
            <span class="form-page-icon"><x-icon name="wallet" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ $account->name }}</h1>
                <p class="text-sm text-text-muted">{{ $account->type->label() }} · {{ __('current balance') }} <strong class="text-text-primary dark:text-white">{{ $money($account->current_balance) }}</strong></p>
            </div>
            <a href="{{ route('accounts.index') }}" class="btn-ghost"><x-icon name="arrow-left" :size="15" /> {{ __('All accounts') }}</a>
        </div>

        <form method="GET" class="flex flex-wrap items-end gap-3 border border-border bg-surface p-4 dark:border-white/10 dark:bg-surface-dark">
            <div><label class="text-xs font-semibold text-text-muted">{{ __('From') }}</label><input type="date" name="from" value="{{ $from }}" class="mt-1 block border-border text-sm"></div>
            <div><label class="text-xs font-semibold text-text-muted">{{ __('To') }}</label><input type="date" name="to" value="{{ $to }}" class="mt-1 block border-border text-sm"></div>
            <button class="btn-primary"><x-icon name="filter" :size="14" /> {{ __('Show') }}</button>
            <a href="{{ route('accounts.ledger', $account) }}" class="btn-ghost">{{ __('This month') }}</a>
            @can('reports.fundsFlow')
                <a href="{{ route('reports.funds-flow', array_filter(['account_id' => $account->id, 'from' => $from, 'to' => $to])) }}" class="btn-ghost ml-auto"><x-icon name="chart-bar" :size="14" /> {{ __('Funds flow') }}</a>
            @endcan
        </form>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <x-card class="px-4 py-3"><div class="text-xs uppercase tracking-wider text-text-muted">{{ __('Opening') }}</div><div class="text-xl font-bold text-text-primary dark:text-white">{{ $money($statement['opening']) }}</div></x-card>
            <x-card class="px-4 py-3"><div class="text-xs uppercase tracking-wider text-text-muted">{{ __('Money in') }}</div><div class="text-xl font-bold text-status-paid">{{ $money($statement['in']) }}</div></x-card>
            <x-card class="px-4 py-3"><div class="text-xs uppercase tracking-wider text-text-muted">{{ __('Money out') }}</div><div class="text-xl font-bold text-status-cancelled">{{ $money($statement['out']) }}</div></x-card>
            <x-card class="px-4 py-3"><div class="text-xs uppercase tracking-wider text-text-muted">{{ __('Closing') }}</div><div class="text-xl font-bold text-text-primary dark:text-white">{{ $money($statement['closing']) }}</div></x-card>
        </div>

        <x-card :padded="false">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                    <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">What</th>
                            <th class="px-4 py-3">Party</th>
                            <th class="px-4 py-3 text-right">In</th>
                            <th class="px-4 py-3 text-right">Out</th>
                            <th class="px-4 py-3 text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60 dark:divide-white/5">
                        <tr class="bg-bg/60 text-text-muted dark:bg-white/5">
                            <td class="px-4 py-2" colspan="5">{{ __('Opening balance') }}@if ($from) · {{ \Illuminate\Support\Carbon::parse($from)->format('M j, Y') }}@endif</td>
                            <td class="px-4 py-2 text-right font-semibold">{{ number_format($statement['opening'], 2) }}</td>
                        </tr>
                        @forelse ($statement['rows'] as $tx)
                            <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                <td class="whitespace-nowrap px-4 py-2.5">{{ $tx->occurred_on?->format('M j, Y') ?? $tx->created_at->format('M j, Y') }}</td>
                                <td class="px-4 py-2.5">
                                    @if ($tx->flow['url'])
                                        <a href="{{ $tx->flow['url'] }}" class="font-medium text-text-primary hover:text-brand-primary dark:text-white">{{ $tx->flow['title'] }}</a>
                                    @else
                                        <span class="font-medium text-text-primary dark:text-white">{{ $tx->flow['title'] }}</span>
                                    @endif
                                    <span class="block max-w-md truncate text-xs text-text-muted" title="{{ $tx->description }}">{{ $tx->description }}</span>
                                </td>
                                <td class="px-4 py-2.5 text-text-muted">{{ $tx->flow['party'] ?: '—' }}</td>
                                <td class="px-4 py-2.5 text-right font-medium text-status-paid">{{ $tx->type->sign() > 0 ? number_format((float) $tx->amount, 2) : '' }}</td>
                                <td class="px-4 py-2.5 text-right font-medium text-status-cancelled">{{ $tx->type->sign() < 0 ? number_format((float) $tx->amount, 2) : '' }}</td>
                                <td class="px-4 py-2.5 text-right">{{ number_format($tx->balance_after, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td class="px-4 py-6 text-center text-text-muted" colspan="6">{{ __('No movements in this period.') }}</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="border-t border-border/60 text-sm font-semibold dark:border-white/10">
                        <tr>
                            <td class="px-4 py-3" colspan="3">{{ __('Closing balance') }}</td>
                            <td class="px-4 py-3 text-right text-status-paid">{{ number_format($statement['in'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-status-cancelled">{{ number_format($statement['out'], 2) }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($statement['closing'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-card>
    </div>
</x-app-layout>
