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
    </div>
</x-app-layout>
