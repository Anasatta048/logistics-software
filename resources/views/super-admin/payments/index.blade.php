<x-app-layout>
    <x-slot name="header">{{ __('Payments') }}</x-slot>

    <div class="space-y-5">
        <div class="form-page-head !mb-0 flex-wrap">
            <span class="form-page-icon"><x-icon name="wallet" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Subscription payments') }}</h1>
                <p class="text-sm text-text-muted">{{ __('Check the slip, then approve to extend the workspace or reject with a reason.') }}</p>
            </div>
        </div>

        @if (session('success'))<x-alert variant="success">{{ session('success') }}</x-alert>@endif
        @if ($errors->any())<x-alert variant="error">{{ $errors->first() }}</x-alert>@endif

        <div class="flex flex-wrap items-center gap-2">
            @foreach ([null => __('All'), 'pending' => __('Pending'), 'approved' => __('Approved'), 'rejected' => __('Rejected')] as $key => $label)
                <a href="{{ route('super-admin.payments.index', array_filter(['status' => $key, 'q' => request('q')])) }}"
                   class="px-3 py-1.5 text-xs font-bold {{ $status === $key ? 'bg-brand-navy text-white' : 'border border-border text-text-muted hover:border-brand-primary dark:border-white/10' }}">
                    {{ $label }}@if ($key) <span class="opacity-70">({{ $counts[$key] ?? 0 }})</span>@endif
                </a>
            @endforeach
            <form method="GET" class="ml-auto flex gap-2">
                @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Workspace, reference, receipt') }}" class="w-60 border-border text-sm focus:border-brand-primary focus:ring-brand-primary">
                <button class="btn-ghost btn-sm"><x-icon name="search" :size="14" /></button>
            </form>
        </div>

        <x-card :padded="false">
            @if ($payments->isEmpty())
                <x-empty-state icon="wallet" title="No payments found" description="Nothing matches this filter yet." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr><th class="px-4 py-3">{{ __('Workspace') }}</th><th class="px-4 py-3">{{ __('Plan') }}</th><th class="px-4 py-3">{{ __('Amount') }}</th><th class="px-4 py-3">{{ __('Via / reference') }}</th><th class="px-4 py-3">{{ __('Reported') }}</th><th class="px-4 py-3">{{ __('Status') }}</th><th class="px-4 py-3"></th></tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($payments as $p)
                                <tr x-data="{ rejecting: false }" class="align-top">
                                    <td class="px-4 py-3 font-medium text-text-primary dark:text-white"><a href="{{ route('super-admin.tenants.show', $p->tenant_id) }}" class="hover:text-brand-primary">{{ $p->tenant->name }}</a></td>
                                    <td class="px-4 py-3">{{ $p->plan?->name }}<br><span class="text-xs text-text-muted">{{ $p->billing_cycle }}</span></td>
                                    <td class="px-4 py-3 font-semibold">{{ $p->money() }}</td>
                                    <td class="px-4 py-3">{{ __($p->methodLabel()) }}<br><span class="text-xs text-text-muted">{{ $p->reference ?: '—' }}</span>
                                        @if ($p->note)<br><span class="text-xs text-text-muted">“{{ $p->note }}”</span>@endif</td>
                                    <td class="px-4 py-3 text-text-muted">{{ $p->created_at->format(df()) }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 text-[11px] font-bold {{ ['pending' => 'bg-brand-gold/15 text-brand-gold', 'approved' => 'bg-status-paid/15 text-status-paid', 'rejected' => 'bg-status-cancelled/15 text-status-cancelled'][$p->status] }}">{{ ucfirst($p->status) }}</span>
                                        @if ($p->receipt_number)<br><span class="text-xs text-text-muted">{{ $p->receipt_number }}</span>@endif
                                        @if ($p->review_note)<br><span class="text-xs text-text-muted">{{ $p->review_note }}</span>@endif
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        @if ($p->proof_path)<a href="{{ route('super-admin.payments.proof', $p) }}" class="btn-ghost btn-sm" title="{{ __('Download slip') }}"><x-icon name="download" :size="14" /> {{ __('Slip') }}</a>@endif
                                        @if ($p->isPending())
                                            <form method="POST" action="{{ route('super-admin.payments.approve', $p) }}" class="inline">@csrf
                                                <button class="btn-primary btn-sm" data-confirm="Approve {{ $p->money() }} from {{ $p->tenant->name }}? This extends the workspace.">{{ __('Approve') }}</button>
                                            </form>
                                            <button type="button" @click="rejecting = !rejecting" class="btn-ghost btn-sm !text-status-cancelled">{{ __('Reject') }}</button>
                                            <form method="POST" action="{{ route('super-admin.payments.reject', $p) }}" x-show="rejecting" x-cloak class="mt-2 flex gap-2">@csrf
                                                <input type="text" name="reason" required maxlength="500" placeholder="{{ __('Reason shown to the customer') }}" class="w-56 border-border text-sm">
                                                <button class="btn-ghost btn-sm">{{ __('Send') }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        <div>{{ $payments->links() }}</div>
    </div>
</x-app-layout>
