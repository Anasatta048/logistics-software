<x-app-layout>
    <x-slot name="header">{{ __('Commissions') }}</x-slot>

    <div class="space-y-6">
        <div class="form-page-head !mb-0 flex-wrap">
            <span class="form-page-icon"><x-icon name="percent" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Commissions') }}</h1>
                <p class="text-sm text-text-muted">
                    {{ __('Earned automatically when a load is marked paid. Pay a dispatcher any amount — part now, the rest later, or ahead of time. Every movement is kept in their ledger.') }}
                </p>
            </div>
            @can('pay', App\Models\Commission::class)
                <x-button variant="primary" as="a" href="{{ route('commissions.payouts.create') }}">
                    <x-icon name="wallet" :size="16" /> {{ __('Record payout') }}
                </x-button>
            @endcan
        </div>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ([
                __('Earned') => $positions->sum(fn ($row) => $row->position['accrued']),
                __('Paid out') => $positions->sum(fn ($row) => $row->paid),
                __('Still owed') => $positions->sum(fn ($row) => $row->position['outstanding']),
                __('Paid in advance') => $positions->sum(fn ($row) => $row->position['advance']),
            ] as $label => $amount)
                <x-card class="px-4 py-3">
                    <div class="text-xs uppercase tracking-wider text-text-muted">{{ $label }}</div>
                    <div class="text-xl font-semibold text-text-primary dark:text-white">${{ number_format($amount, 2) }}</div>
                </x-card>
            @endforeach
        </div>

        @php
            $hasWindow = request('from') || request('to');
            $reportQuery = array_filter(['from' => request('from'), 'to' => request('to'), 'user_id' => request('dispatcher_id')]);
        @endphp
        <x-card>
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-sm font-semibold text-text-primary dark:text-white">
                    {{ $hasWindow ? __('Report for :from – :to', ['from' => request('from') ?: __('start'), 'to' => request('to') ?: __('today')]) : __('Report — all time') }}
                </h3>
                @can('reports.dispatcherCommissions')
                    <div class="flex items-center gap-2">
                        <a data-no-loader href="{{ route('reports.dispatcher-commissions', $reportQuery + ['export' => 'csv']) }}" class="btn-ghost btn-sm"><x-icon name="download" :size="14" /> CSV</a>
                        <a data-no-loader href="{{ route('reports.dispatcher-commissions', $reportQuery + ['export' => 'pdf']) }}" class="btn-primary btn-sm"><x-icon name="printer" :size="14" /> PDF</a>
                        <a href="{{ route('reports.dispatcher-commissions', $reportQuery) }}" class="text-sm text-brand-primary hover:underline">{{ __('Full report') }}</a>
                    </div>
                @endcan
            </div>
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
                <x-reports.stat :label="__('Loads')" :value="$period['loads']" />
                <x-reports.stat :label="__('Commission earned')" :value="$period['earned']" :money="true" />
                <x-reports.stat :label="__('Already paid')" :value="$period['paid']" :money="true" />
                <x-reports.stat :label="__('Still payable')" :value="$period['payable']" :money="true" :tone="$period['payable'] > 0 ? 'bad' : 'good'" />
                <x-reports.stat :label="__('Payouts made')" :value="$period['payouts_usd']" :money="true" :hint="'PKR '.number_format($period['payouts_pkr'], 2)" />
            </div>
            <p class="mt-3 text-xs text-text-muted">{{ __('Set From / To in the filter below to change the period.') }}</p>
        </x-card>

        <x-card :padded="false">
            <div class="border-b border-border/60 px-4 py-3 dark:border-white/10">
                <h3 class="text-sm font-semibold text-text-primary dark:text-white">{{ __('Dispatcher balances') }}</h3>
            </div>
            <div class="overflow-x-auto">
                <table data-table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                    <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                        <tr>
                            <th class="px-4 py-3">{{ __('Dispatcher') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Earned') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Paid out') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Balance') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60 dark:divide-white/5">
                        @forelse ($positions as $row)
                            <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                <td class="px-4 py-3 font-medium text-text-primary dark:text-white">{{ $row->user->name }}</td>
                                <td class="px-4 py-3 text-right">${{ number_format($row->position['accrued'], 2) }}</td>
                                <td class="px-4 py-3 text-right">${{ number_format($row->paid, 2) }}</td>
                                <td class="px-4 py-3 text-right font-semibold">
                                    @if ($row->position['balance'] < 0)
                                        <span class="text-blue-600 dark:text-blue-300">${{ number_format($row->position['advance'], 2) }}</span>
                                        <span class="ml-1 rounded-full bg-blue-100 px-1.5 py-0.5 text-[10px] font-semibold text-blue-800 dark:bg-blue-500/20 dark:text-blue-300">{{ __('ADVANCE') }}</span>
                                    @elseif ($row->position['balance'] > 0)
                                        <span class="text-status-cancelled">${{ number_format($row->position['outstanding'], 2) }}</span>
                                        <span class="ml-1 text-xs font-normal text-text-muted">{{ __('owed') }}</span>
                                    @else
                                        <span class="text-status-paid">{{ __('Settled') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('commissions.ledger', $row->user) }}" class="text-brand-primary hover:underline">{{ __('Ledger') }}</a>
                                    @can('pay', App\Models\Commission::class)
                                        <a href="{{ route('commissions.payouts.create', ['dispatcher_id' => $row->user->id]) }}" class="ml-3 text-brand-primary hover:underline">{{ __('Pay') }}</a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-text-muted">{{ __('No dispatchers yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <x-card>
            <form method="GET" action="{{ route('commissions.index') }}" class="flex flex-wrap items-end gap-3">
                <div>
                    <x-input-label for="q" :value="__('Search')" />
                    <x-text-input id="q" name="q" type="search" class="mt-1 block w-56"
                        :value="request('q')" placeholder="Load reference" />
                </div>
                <div>
                    <x-input-label for="status" :value="__('Status')" />
                    <select id="status" name="status"
                        class="mt-1 block rounded-input border-border bg-surface text-text-primary shadow-sm focus:border-brand-primary focus:ring-brand-primary dark:bg-surface-dark dark:border-white/10 dark:text-white">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if ($dispatchers->isNotEmpty())
                    <div>
                        <x-input-label for="dispatcher_id" :value="__('Dispatcher')" />
                        <select id="dispatcher_id" name="dispatcher_id"
                            class="mt-1 block rounded-input border-border bg-surface text-text-primary shadow-sm focus:border-brand-primary focus:ring-brand-primary dark:bg-surface-dark dark:border-white/10 dark:text-white">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($dispatchers as $dispatcher)
                                <option value="{{ $dispatcher->id }}" @selected((string) request('dispatcher_id') === (string) $dispatcher->id)>
                                    {{ $dispatcher->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <x-input-label for="from" :value="__('From')" />
                    <x-text-input id="from" name="from" type="date" class="mt-1 block w-40" :value="request('from')" />
                </div>
                <div>
                    <x-input-label for="to" :value="__('To')" />
                    <x-text-input id="to" name="to" type="date" class="mt-1 block w-40" :value="request('to')" />
                </div>
                <x-button variant="primary" type="submit">{{ __('Filter') }}</x-button>
                <a href="{{ route('commissions.index') }}" class="text-sm text-text-muted hover:underline">
                    {{ __('Reset') }}
                </a>
            </form>
        </x-card>

        <x-card :padded="false">
            @if ($commissions->isEmpty())
                <x-empty-state icon="wallet" title="No commissions yet" description="Marking a load paid accrues one." />
            @else
                <div class="overflow-x-auto">
                    <table data-table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr>
                                <th class="px-4 py-3">Calculated</th>
                                <th class="px-4 py-3">Load</th>
                                <th class="px-4 py-3">Dispatcher</th>
                                <th class="px-4 py-3 text-right">Margin</th>
                                <th class="px-4 py-3 text-right">Rate</th>
                                <th class="px-4 py-3 text-right">Commission</th>
                                <th class="px-4 py-3 text-right">Paid</th>
                                <th class="px-4 py-3 text-right">Remaining</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($commissions as $commission)
                                <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <x-datetime :value="$commission->calculated_at" />
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ $commission->loadRecord?->reference_number ?? __('Load') }} #{{ $commission->load_id }}
                                    </td>
                                    <td class="px-4 py-3">{{ $commission->dispatcher?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right {{ (float) $commission->margin_amount < 0 ? 'text-status-cancelled' : '' }}">
                                        ${{ number_format((float) $commission->margin_amount, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-text-muted">
                                        {{ $commission->commission_type === \App\Enums\CommissionType::Fixed
                                            ? '$'.number_format((float) $commission->commission_fixed_amount, 2).' fixed'
                                            : rtrim(rtrim((string) $commission->commission_percentage, '0'), '.').'%' }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-text-primary dark:text-white">
                                        ${{ number_format((float) $commission->commission_amount, 2) }}
                                    </td>
                                    @php
                                        $item = $coverage[$commission->load_id] ?? null;
                                        $reversed = $commission->status === \App\Enums\CommissionStatus::Reversed;
                                        $done = $item === null ? ! $reversed : $item->remaining <= 0.005;
                                        $partly = $item !== null && ! $done && $item->applied > 0.005;
                                    @endphp
                                    <td class="px-4 py-3 text-right text-status-paid">{{ $item ? '$'.number_format($item->applied, 2) : '—' }}</td>
                                    <td class="px-4 py-3 text-right font-medium">{{ $reversed ? '—' : ($item ? '$'.number_format($item->remaining, 2) : '$0.00') }}</td>
                                    <td class="px-4 py-3">
                                        @if ($reversed)
                                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700 dark:bg-white/10 dark:text-gray-200">{{ __('Reversed') }}</span>
                                        @elseif ($done)
                                            <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-500/20 dark:text-green-300">{{ __('Paid') }}</span>
                                        @elseif ($partly)
                                            <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-500/20 dark:text-amber-300">{{ __('Partly paid') }}</span>
                                        @else
                                            <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800 dark:bg-red-500/20 dark:text-red-300">{{ __('Unpaid') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        <div>{{ $commissions->links() }}</div>
    </div>
</x-app-layout>
