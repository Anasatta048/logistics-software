<x-app-layout>
    <x-slot name="header">{{ __('SaaS Overview') }}</x-slot>

    <div class="space-y-6">
        <div class="form-page-head !mb-0 flex-wrap">
            <span class="form-page-icon"><x-icon name="layers" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('SaaS Overview') }}</h1>
                <p class="text-sm text-text-muted">{{ __('Plans, and the companies asking to join.') }}</p>
            </div>
            <a href="{{ route('super-admin.requests.index', ['status' => 'pending']) }}" class="btn-primary"><x-icon name="inbox" :size="16" /> {{ __('Review requests') }}</a>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                [__('Pending requests'), $pending, 'clock', 'text-brand-gold'],
                [__('Approved'), $approved, 'check-circle', 'text-status-paid'],
                [__('Rejected'), $rejected, 'x-circle', 'text-status-cancelled'],
                [__('Active plans'), $plansActive, 'tag', 'text-brand-primary'],
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

            <x-card>
                <h2 class="font-bold text-text-primary dark:text-white">{{ __('Interest by plan') }}</h2>
                @php($max = max(1, $planDemand->max('requests_count')))
                <div class="mt-4 space-y-3">
                    @foreach ($planDemand as $plan)
                        <div>
                            <div class="mb-1 flex justify-between text-xs">
                                <span class="font-semibold text-text-primary dark:text-white">{{ $plan->name }}</span>
                                <span class="text-text-muted">{{ $plan->requests_count }}</span>
                            </div>
                            <div class="h-1.5 w-full bg-border dark:bg-white/10"><div class="h-full bg-brand-primary" style="width: {{ $plan->requests_count / $max * 100 }}%"></div></div>
                        </div>
                    @endforeach
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
