<x-app-layout>
    <x-slot name="header">{{ __('SaaS Overview') }}</x-slot>

    <div class="space-y-6">
        <div class="form-page-head !mb-0 flex-wrap">
            <span class="form-page-icon"><x-icon name="layers" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('SaaS Overview') }}</h1>
                <p class="text-sm text-text-muted">{{ __('Revenue, workspaces and the companies asking to join.') }}</p>
            </div>
            <a href="{{ route('super-admin.requests.index', ['status' => 'pending']) }}" class="btn-primary"><x-icon name="inbox" :size="16" /> {{ __('Review requests') }} @if ($pending)<span class="bg-white/25 px-1.5 text-xs">{{ $pending }}</span>@endif</a>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                [__('Monthly recurring revenue'), '$'.number_format($mrr, 0), 'wallet', 'text-status-paid'],
                [__('Active workspaces'), $tenantCounts['active'] ?? 0, 'building', 'text-brand-primary'],
                [__('On trial'), $tenantCounts['trial'] ?? 0, 'clock', 'text-brand-gold'],
                [__('Suspended'), $tenantCounts['suspended'] ?? 0, 'lock', 'text-warning'],
                [__('New in 30 days'), $newTenants, 'sparkles', 'text-brand-navy-light'],
                [__('Trial → paid'), $conversion.'%', 'chart-bar', 'text-status-paid'],
                [__('Pending requests'), $pending, 'inbox', 'text-brand-gold'],
                [__('Approval rate'), $approvalRate.'%', 'check-circle', 'text-status-paid'],
            ] as [$label, $value, $icon, $tone])
                <x-card>
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wider text-text-muted">{{ $label }}</p>
                        <span class="{{ $tone }}"><x-icon :name="$icon" :size="20" /></span>
                    </div>
                    <p class="mt-2 text-3xl font-extrabold text-text-primary dark:text-white">{{ $value }}</p>
                </x-card>
            @endforeach
        </div>

        <div class="grid gap-5 lg:grid-cols-[1.6fr_1fr]">
            <x-card :padded="false">
                <div class="flex items-center justify-between border-b border-border px-5 py-3 dark:border-white/10">
                    <h2 class="font-bold text-text-primary dark:text-white">{{ __('Latest requests') }}</h2>
                    <a href="{{ route('super-admin.requests.index') }}" class="text-xs font-semibold text-brand-primary">{{ __('View all') }}</a>
                </div>
                @forelse ($latest as $req)
                    <a href="{{ route('super-admin.requests.show', $req) }}" class="flex items-center gap-3 border-b border-border/60 px-5 py-3 last:border-0 hover:bg-bg/60 dark:border-white/5 dark:hover:bg-white/5">
                        <span class="grid h-9 w-9 shrink-0 place-items-center bg-brand-navy text-xs font-bold text-white">{{ strtoupper(substr($req->company_name, 0, 2)) }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-text-primary dark:text-white">{{ $req->company_name }}</p>
                            <p class="truncate text-xs text-text-muted">{{ $req->contact_name }} · {{ $req->plan?->name ?? __('No plan chosen') }} · {{ $req->created_at->diffForHumans() }}</p>
                        </div>
                        <span class="px-2 py-0.5 text-[11px] font-bold {{ $req->status->color() }}">{{ $req->status->label() }}</span>
                    </a>
                @empty
                    <x-empty-state icon="inbox" title="No requests yet" description="Requests from the landing page will show up here." />
                @endforelse
            </x-card>

            <div class="space-y-5">
                <x-card>
                    <h2 class="font-bold text-text-primary dark:text-white">{{ __('Plans') }}</h2>
                    @php($max = max(1, $planDemand->max('requests_count')))
                    <div class="mt-4 space-y-3">
                        @foreach ($planDemand as $plan)
                            <div>
                                <div class="mb-1 flex justify-between text-xs">
                                    <span class="font-semibold text-text-primary dark:text-white">{{ $plan->name }}</span>
                                    <span class="text-text-muted">{{ $plan->tenants_count }} {{ __('workspaces') }} · {{ $plan->requests_count }} {{ __('requests') }}</span>
                                </div>
                                <div class="h-1.5 w-full bg-border dark:bg-white/10"><div class="h-full bg-brand-primary" style="width: {{ $plan->requests_count / $max * 100 }}%"></div></div>
                            </div>
                        @endforeach
                    </div>
                </x-card>

                <x-card :padded="false">
                    <h2 class="border-b border-border px-5 py-3 font-bold text-text-primary dark:border-white/10 dark:text-white">{{ __('Ending within 7 days') }}</h2>
                    @forelse ($endingSoon as $t)
                        <a href="{{ route('super-admin.tenants.show', $t) }}" class="flex items-center justify-between border-b border-border/60 px-5 py-2.5 text-sm last:border-0 hover:bg-bg/60 dark:border-white/5 dark:hover:bg-white/5">
                            <span class="truncate font-medium text-text-primary dark:text-white">{{ $t->name }}</span>
                            <span class="shrink-0 text-xs text-text-muted">{{ $t->status->label() }} · {{ $t->endsAt()?->format('d M') }}</span>
                        </a>
                    @empty
                        <p class="px-5 py-4 text-sm text-text-muted">{{ __('Nothing expiring soon.') }}</p>
                    @endforelse
                </x-card>
            </div>
        </div>

        <x-card :padded="false">
            <h2 class="border-b border-border px-5 py-3 font-bold text-text-primary dark:border-white/10 dark:text-white">{{ __('Platform activity') }}</h2>
            @forelse ($audit as $log)
                <div class="flex items-center gap-3 border-b border-border/60 px-5 py-2.5 text-sm last:border-0 dark:border-white/5">
                    <span class="w-36 shrink-0 text-xs text-text-muted">{{ $log->created_at->format('d M Y H:i') }}</span>
                    <span class="flex-1">{{ $log->description ?: $log->action }}</span>
                    <span class="text-xs text-text-muted">{{ $log->user?->name }}</span>
                </div>
            @empty
                <p class="px-5 py-4 text-sm text-text-muted">{{ __('Nothing yet.') }}</p>
            @endforelse
        </x-card>
    </div>
</x-app-layout>
