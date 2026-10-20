<x-app-layout>
    <x-slot name="header">{{ __('Access Requests') }}</x-slot>

    <div class="space-y-5">
        <div class="form-page-head !mb-0 flex-wrap">
            <span class="form-page-icon"><x-icon name="inbox" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Access requests') }}</h1>
                <p class="text-sm text-text-muted">{{ __('Companies that asked for a demo or account from the landing page.') }}</p>
            </div>
        </div>

        @if (session('success'))<x-alert variant="success">{{ session('success') }}</x-alert>@endif

        <div class="flex flex-wrap items-center gap-2">
            @foreach ([null => __('All'), 'pending' => __('Pending'), 'approved' => __('Approved'), 'rejected' => __('Rejected')] as $key => $label)
                <a href="{{ route('super-admin.requests.index', array_filter(['status' => $key, 'q' => request('q')])) }}"
                   class="px-3 py-1.5 text-xs font-bold {{ ($status?->value) === $key ? 'bg-brand-navy text-white' : 'border border-border text-text-muted hover:border-brand-primary dark:border-white/10' }}">
                    {{ $label }}@if ($key) <span class="opacity-70">({{ $counts[$key] ?? 0 }})</span>@endif
                </a>
            @endforeach
            <form method="GET" class="ml-auto flex gap-2">
                @if ($status)<input type="hidden" name="status" value="{{ $status->value }}">@endif
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Search company, name, email') }}" class="w-60 border-border text-sm focus:border-brand-primary focus:ring-brand-primary">
                <button class="btn-ghost btn-sm"><x-icon name="search" :size="14" /></button>
            </form>
        </div>

        <x-card :padded="false">
            @if ($requests->isEmpty())
                <x-empty-state icon="inbox" title="No requests found" description="Nothing matches this filter yet." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr><th class="px-4 py-3">Company</th><th class="px-4 py-3">Contact</th><th class="px-4 py-3">Fleet</th><th class="px-4 py-3">Plan</th><th class="px-4 py-3">Received</th><th class="px-4 py-3">Status</th><th class="px-4 py-3"></th></tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($requests as $req)
                                <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 font-medium text-text-primary dark:text-white">{{ $req->company_name }}</td>
                                    <td class="px-4 py-3">{{ $req->contact_name }}<br><span class="text-xs text-text-muted">{{ $req->email }}</span></td>
                                    <td class="px-4 py-3">{{ $req->fleet_size ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $req->plan?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-text-muted">{{ $req->created_at->format(df()) }}</td>
                                    <td class="px-4 py-3"><span class="px-2 py-0.5 text-[11px] font-bold {{ $req->status->color() }}">{{ $req->status->label() }}</span></td>
                                    <td class="px-4 py-3 text-right"><a href="{{ route('super-admin.requests.show', $req) }}" class="text-text-muted hover:text-brand-primary" title="{{ __('Open') }}"><x-icon name="eye" :size="16" /></a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        <div>{{ $requests->links() }}</div>
    </div>
</x-app-layout>
