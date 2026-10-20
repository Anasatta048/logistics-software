<x-app-layout>
    <x-slot name="header">{{ __('Dashboard') }}</x-slot>

    @php
        $hour = now()->hour;
        $greeting = $hour < 12 ? __('Good morning') : ($hour < 18 ? __('Good afternoon') : __('Good evening'));
        // Company money (margin, settlements) is admin-only; everyone else sees
        // their own activity and commission.
        $isAdmin = auth()->user()->can('view-company-commission');
        $kpis = [
            ['label' => "Today's Bookings", 'hint' => 'Loads booked today', 'value' => $metrics['today_bookings'], 'icon' => 'truck', 'tone' => 'bg-brand-primary/10 text-brand-primary', 'prefix' => ''],
            ['label' => 'Active Loads', 'hint' => 'Currently in progress', 'value' => array_sum($metrics['active_by_status']), 'icon' => 'chart-bar', 'tone' => 'bg-brand-navy/10 text-brand-navy dark:bg-white/10 dark:text-white', 'prefix' => ''],
        ];
        if ($isAdmin) {
            $kpis[] = ['label' => "This Month's Margin", 'hint' => 'Profit earned this month', 'value' => number_format($metrics['month_margin'], 2), 'icon' => 'wallet', 'tone' => 'bg-status-paid/10 text-status-paid', 'prefix' => '$'];
            $kpis[] = ['label' => 'Pending Settlements', 'hint' => 'Still waiting to be paid', 'value' => number_format($metrics['pending_settlement'], 2), 'icon' => 'receipt', 'tone' => 'bg-brand-gold/15 text-brand-gold', 'prefix' => '$'];
        } else {
            $kpis[] = ['label' => "This Month's Commission", 'hint' => 'Your commission earned this month', 'value' => number_format($metrics['month_commission'], 2), 'icon' => 'wallet', 'tone' => 'bg-status-paid/10 text-status-paid', 'prefix' => '$'];
            $kpis[] = ['label' => 'Available Drivers', 'hint' => 'Free to take a load', 'value' => $metrics['available_drivers'], 'icon' => 'truck', 'tone' => 'bg-brand-gold/15 text-brand-gold', 'prefix' => ''];
        }
    @endphp

    <div class="space-y-6">
        {{-- Header + quick actions --}}
        <div class="form-anim form-page-head !mb-0 flex-wrap">
            <span class="form-page-icon"><x-icon name="dashboard" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">
                    {{ $greeting }}, {{ Str::of(Auth::user()->name)->before(' ') }}
                </h1>
                <p class="mt-0.5 text-sm text-text-muted">{{ __("Here's how dispatch is moving today.") }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if (Route::has('loads.create'))
                    @can('create', App\Models\Load::class)
                        <a href="{{ route('loads.create') }}" class="btn-primary btn-sm">
                            <x-icon name="plus" :size="16" /> {{ __('New load') }}
                        </a>
                    @endcan
                @endif
                @if (Route::has('invoices.create'))
                    @can('create', App\Models\Invoice::class)
                        <a href="{{ route('invoices.create') }}" class="btn-ghost btn-sm">
                            <x-icon name="file-text" :size="16" /> {{ __('New invoice') }}
                        </a>
                    @endcan
                @endif
                @if (Route::has('company-payments.create'))
                    @can('create', App\Models\CompanyPayment::class)
                        <a href="{{ route('company-payments.create') }}" class="btn-ghost btn-sm">
                            <x-icon name="credit-card" :size="16" /> {{ __('Record payment') }}
                        </a>
                    @endcan
                @endif
            </div>
        </div>

        {{-- Get set up (new workspaces only) --}}
        @if (! empty($business['onboarding']))
            @php($ob = $business['onboarding'])
            <section class="form-anim border border-brand-primary/40 bg-brand-primary/5 p-5">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center bg-brand-primary text-white"><x-icon name="sparkles" :size="20" /></span>
                    <div class="min-w-0 flex-1">
                        <h2 class="font-bold text-text-primary dark:text-white">{{ __('Get your workspace ready') }}</h2>
                        <p class="text-sm text-text-muted">{{ $ob['done'] }} / {{ $ob['total'] }} {{ __('steps done') }}</p>
                    </div>
                    <div class="h-2 w-40 bg-border dark:bg-white/10"><div class="h-full bg-brand-primary" style="width: {{ $ob['done'] / $ob['total'] * 100 }}%"></div></div>
                </div>
                <ul class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($ob['steps'] as $step)
                        <li>
                            <a href="{{ $step['url'] }}" class="flex items-center gap-2 border border-border bg-surface px-3 py-2 text-sm hover:border-brand-primary dark:border-white/10 dark:bg-surface-dark {{ $step['done'] ? 'text-text-muted line-through' : 'font-semibold text-text-primary dark:text-white' }}">
                                <x-icon :name="$step['done'] ? 'check-circle' : 'plus'" :size="16" class="{{ $step['done'] ? 'text-status-paid' : 'text-brand-primary' }}" /> {{ __($step['label']) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- Alerts --}}
        @if (! empty($business['alerts']))
            <div class="form-anim space-y-2">
                @foreach ($business['alerts'] as $alert)
                    @php($tone = ['danger' => 'border-status-cancelled/40 bg-status-cancelled/5 text-status-cancelled', 'warning' => 'border-warning/40 bg-warning/5 text-warning', 'info' => 'border-brand-navy-light/40 bg-brand-navy-light/5 text-brand-navy-light'][$alert['tone']])
                    <a href="{{ $alert['url'] ?? '#' }}" class="flex items-center gap-3 border px-4 py-2.5 text-sm font-semibold {{ $tone }}">
                        <x-icon name="alert-triangle" :size="16" /> <span class="flex-1">{{ $alert['text'] }}</span> <x-icon name="chevron-right" :size="14" />
                    </a>
                @endforeach
            </div>
        @endif

        {{-- KPI tiles --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($kpis as $card)
                <div class="form-anim dash-kpi border border-border bg-surface p-5 dark:border-white/10 dark:bg-surface-dark">
                    <div class="flex items-start gap-4">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center {{ $card['tone'] }}">
                            <x-icon :name="$card['icon']" :size="20" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-text-muted">{{ __($card['label']) }}</p>
                            <p class="mt-1 truncate text-2xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ $card['prefix'] }}{{ $card['value'] }}</p>
                            <p class="mt-0.5 text-xs text-text-muted">{{ __($card['hint']) }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Needs attention --}}
        <section class="form-anim form-section">
            <div class="form-section-head">
                <span class="form-section-icon"><x-icon name="alert-triangle" :size="18" /></span>
                <div class="min-w-0 flex-1">
                    <h2 class="form-section-title">{{ __('Needs Attention') }}</h2>
                    <p class="form-section-desc">{{ __('Loads whose pickup time has passed but have not moved yet.') }}</p>
                </div>
                @if ($metrics['needs_attention']->isNotEmpty())
                    <span class="inline-flex h-6 min-w-6 items-center justify-center bg-brand-primary px-2 text-xs font-bold text-white">{{ $metrics['needs_attention']->count() }}</span>
                @endif
            </div>

            @if ($metrics['needs_attention']->isEmpty())
                <x-empty-state icon="check-circle" title="Nothing needs attention right now"
                    description="All loads are moving on schedule." />
            @else
                <ul class="divide-y divide-border dark:divide-white/10">
                    @foreach ($metrics['needs_attention'] as $load)
                        <li>
                            <a href="{{ route('loads.edit', $load) }}"
                               class="group flex flex-wrap items-center gap-3 px-1 py-3 transition hover:bg-brand-primary/5 sm:flex-nowrap">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center bg-brand-primary/10 text-brand-primary">
                                    <x-icon name="clock" :size="16" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-text-primary group-hover:text-brand-primary dark:text-white">{{ $load->reference_number }}</p>
                                    <p class="text-xs text-text-muted">{{ __('Pickup was') }} {{ $load->pickup_at->diffForHumans() }}</p>
                                </div>
                                <x-status-badge :status="$load->status" />
                                <span class="text-xs font-semibold text-brand-primary">{{ __('Open') }} &rarr;</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Charts --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            @if ($isAdmin)
            <div class="form-anim lg:col-span-2">
                <x-chart-card title="Revenue & Margin — last 14 days" period="14d">
                    <div class="h-72">
                        <canvas id="revenue-margin-chart"></canvas>
                    </div>
                    <script type="application/json" id="revenue-margin-data">{!! json_encode($metrics['trend']) !!}</script>
                </x-chart-card>
            </div>
            @endif

            <div class="form-anim {{ $isAdmin ? '' : 'lg:col-span-3' }}">
                <x-chart-card title="Loads by Status">
                    <div class="h-72">
                        <canvas id="loads-status-chart"></canvas>
                    </div>
                    <script type="application/json" id="loads-status-data">{!! json_encode($metrics['status_counts']) !!}</script>
                </x-chart-card>
            </div>
        </div>

        @if ($fin = $business['finance'])
            @php($pkr = fn ($v) => 'Rs '.number_format((float) $v, 0))
            <section class="form-anim">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-text-muted">{{ __('Money') }} · {{ $fin['month'] }}</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach (array_filter([
                        ['Fees earned', '$'.number_format($fin['fees'], 2), 'On paid loads this month · gross $'.number_format($fin['gross'], 0), 'wallet', 'bg-status-paid/10 text-status-paid'],
                        $fin['cash'] !== null ? ['Money in accounts', $pkr($fin['cash']['total']), 'Cash and banks right now', 'layers', 'bg-brand-navy/10 text-brand-navy dark:bg-white/10 dark:text-white'] : null,
                        ['Converted to PKR', $pkr($fin['converted_pkr']), 'Reached your accounts this month', 'repeat', 'bg-brand-primary/10 text-brand-primary'],
                        $fin['expenses'] !== null ? ['Spent this month', $pkr($fin['expenses']), $fin['expenses_change'] === null ? 'Office expenses' : ($fin['expenses_change'] >= 0 ? '▲ ' : '▼ ').abs($fin['expenses_change']).'% vs last month', 'receipt', 'bg-status-cancelled/10 text-status-cancelled'] : null,
                        ['USD waiting to convert', '$'.number_format($fin['unconverted_usd'], 2), 'Received, not yet in PKR', 'dollar', 'bg-brand-gold/15 text-brand-gold'],
                        ['Owed to dispatchers', '$'.number_format($fin['commission_payable_usd'], 2), 'Commission earned, unpaid', 'percent', 'bg-brand-gold/15 text-brand-gold'],
                        ['Clients still owe', '$'.number_format($metrics['pending_settlement'], 2), $fin['overdue']['count'].' overdue invoice(s)', 'file-text', 'bg-brand-gold/15 text-brand-gold'],
                        $fin['net_pkr'] !== null ? ['Net this month', $pkr($fin['net_pkr']), 'Converted − expenses − payouts', 'chart-bar', $fin['net_pkr'] >= 0 ? 'bg-status-paid/10 text-status-paid' : 'bg-status-cancelled/10 text-status-cancelled'] : null,
                    ]) as [$label, $value, $hint, $icon, $tone])
                        <div class="dash-kpi border border-border bg-surface p-5 dark:border-white/10 dark:bg-surface-dark">
                            <div class="flex items-start gap-4">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center {{ $tone }}"><x-icon :name="$icon" :size="20" /></span>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-text-muted">{{ __($label) }}</p>
                                    <p class="mt-1 truncate text-2xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ $value }}</p>
                                    <p class="mt-0.5 text-xs text-text-muted">{{ $hint }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            @if (! empty($fin['months']) || ! empty($fin['top_categories']))
                <div class="form-anim grid grid-cols-1 gap-6 lg:grid-cols-3">
                    @if (! empty($fin['months']))
                        @php($peak = max(1, collect($fin['months'])->max(fn ($m) => max($m['income'], $m['spent']))))
                        <div class="border border-border bg-surface p-5 dark:border-white/10 dark:bg-surface-dark lg:col-span-2">
                            <div class="flex items-center justify-between">
                                <h3 class="font-bold text-text-primary dark:text-white">{{ __('Income vs spending — 6 months') }}</h3>
                                <span class="flex gap-3 text-xs text-text-muted"><span class="flex items-center gap-1"><i class="h-2 w-2 bg-status-paid"></i>{{ __('Income') }}</span><span class="flex items-center gap-1"><i class="h-2 w-2 bg-status-cancelled"></i>{{ __('Spent') }}</span></span>
                            </div>
                            <div class="mt-4 flex h-48 items-end gap-3">
                                @foreach ($fin['months'] as $m)
                                    <div class="flex min-w-0 flex-1 flex-col items-center gap-1" title="{{ $m['label'] }}: in Rs {{ number_format($m['income']) }} / out Rs {{ number_format($m['spent']) }}">
                                        <div class="flex h-40 w-full items-end justify-center gap-1">
                                            <div class="w-1/2 max-w-6 bg-status-paid" style="height: {{ max(1, $m['income'] / $peak * 100) }}%"></div>
                                            <div class="w-1/2 max-w-6 bg-status-cancelled" style="height: {{ max(1, $m['spent'] / $peak * 100) }}%"></div>
                                        </div>
                                        <span class="text-[11px] text-text-muted">{{ $m['label'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if (! empty($fin['top_categories']))
                        @php($catMax = max(1, collect($fin['top_categories'])->max('total')))
                        <div class="border border-border bg-surface p-5 dark:border-white/10 dark:bg-surface-dark {{ empty($fin['months']) ? 'lg:col-span-3' : '' }}">
                            <h3 class="font-bold text-text-primary dark:text-white">{{ __('Where it went this month') }}</h3>
                            <div class="mt-4 space-y-3">
                                @foreach ($fin['top_categories'] as $c)
                                    <div>
                                        <div class="mb-1 flex justify-between text-xs"><span class="font-semibold text-text-primary dark:text-white">{{ $c['name'] }}</span><span class="text-text-muted">{{ $pkr($c['total']) }}</span></div>
                                        <div class="h-1.5 bg-border dark:bg-white/10"><div class="h-full bg-brand-primary" style="width: {{ $c['total'] / $catMax * 100 }}%"></div></div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        @endif

        {{-- Plan usage --}}
        @if (! empty($business['usage']))
            <section class="form-anim border border-border bg-surface p-5 dark:border-white/10 dark:bg-surface-dark">
                <h3 class="font-bold text-text-primary dark:text-white">{{ __('Your plan') }} · {{ app(\App\Support\Tenancy\TenantContext::class)->tenant()?->plan?->name }}</h3>
                <div class="mt-3 grid gap-4 sm:grid-cols-3">
                    @foreach ($business['usage'] as $u)
                        <div>
                            <div class="mb-1 flex justify-between text-xs"><span class="font-semibold text-text-primary dark:text-white">{{ $u['label'] }}</span><span class="text-text-muted">{{ $u['used'] }} / {{ $u['limit'] }}</span></div>
                            <div class="h-1.5 bg-border dark:bg-white/10"><div class="h-full {{ $u['used'] >= $u['limit'] * 0.9 ? 'bg-status-cancelled' : 'bg-brand-primary' }}" style="width: {{ min(100, $u['used'] / max(1, $u['limit']) * 100) }}%"></div></div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
