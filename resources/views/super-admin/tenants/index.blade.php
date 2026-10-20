<x-app-layout>
    <x-slot name="header">{{ __('Workspaces') }}</x-slot>

    <div class="space-y-5">
        <div class="form-page-head !mb-0 flex-wrap">
            <span class="form-page-icon"><x-icon name="building" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Workspaces') }}</h1>
                <p class="text-sm text-text-muted">{{ __('Every company running on the platform. Each one sees only its own data.') }}</p>
            </div>
        </div>

        @if (session('success'))<x-alert variant="success">{{ session('success') }}</x-alert>@endif

        <div class="flex flex-wrap items-center gap-2">
            @foreach ([null => __('All'), 'trial' => __('Trial'), 'active' => __('Active'), 'suspended' => __('Suspended'), 'cancelled' => __('Cancelled')] as $key => $label)
                <a href="{{ route('super-admin.tenants.index', array_filter(['status' => $key, 'q' => request('q')])) }}"
                   class="px-3 py-1.5 text-xs font-bold {{ ($status?->value) === $key ? 'bg-brand-navy text-white' : 'border border-border text-text-muted hover:border-brand-primary dark:border-white/10' }}">
                    {{ $label }}@if ($key) <span class="opacity-70">({{ $counts[$key] ?? 0 }})</span>@endif
                </a>
            @endforeach
            <form method="GET" class="ml-auto flex gap-2">
                @if ($status)<input type="hidden" name="status" value="{{ $status->value }}">@endif
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Search name, email') }}" class="w-60 border-border text-sm focus:border-brand-primary focus:ring-brand-primary">
                <button class="btn-ghost btn-sm"><x-icon name="search" :size="14" /></button>
            </form>
        </div>

        <x-card :padded="false">
            @if ($tenants->isEmpty())
                <x-empty-state icon="building" title="No workspaces yet" description="Approve an access request to create the first one." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr><th class="px-4 py-3">Workspace</th><th class="px-4 py-3">Plan</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Ends</th><th class="px-4 py-3">Created</th><th class="px-4 py-3"></th></tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($tenants as $tenant)
                                <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                    <td class="px-4 py-3"><span class="font-medium text-text-primary dark:text-white">{{ $tenant->name }}</span><br><span class="text-xs text-text-muted">{{ $tenant->email }}</span></td>
                                    <td class="px-4 py-3">{{ $tenant->plan?->name ?? '—' }} <span class="text-xs text-text-muted">· {{ $tenant->billing_cycle }}</span></td>
                                    <td class="px-4 py-3"><span class="px-2 py-0.5 text-[11px] font-bold {{ $tenant->status->color() }}">{{ $tenant->status->label() }}</span></td>
                                    <td class="px-4 py-3 text-text-muted">{{ $tenant->endsAt()?->format('d M Y') ?? '—' }}</td>
                                    <td class="px-4 py-3 text-text-muted">{{ $tenant->created_at->format('d M Y') }}</td>
                                    <td class="px-4 py-3 text-right"><a href="{{ route('super-admin.tenants.show', $tenant) }}" class="text-text-muted hover:text-brand-primary" title="{{ __('Open') }}"><x-icon name="eye" :size="16" /></a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        <div>{{ $tenants->links() }}</div>
    </div>
</x-app-layout>
