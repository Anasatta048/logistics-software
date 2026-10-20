<x-app-layout>
    <x-slot name="header">{{ __('Access Request') }}</x-slot>

    <x-form-page :title="$req->company_name" :subtitle="__('Received :when', ['when' => $req->created_at->format('d M Y, H:i')])" icon="inbox" :back="route('super-admin.requests.index')" :back-label="__('All requests')">
        @if (session('success'))<x-alert variant="success">{{ session('success') }}</x-alert>@endif
        @if ($errors->any())<x-alert variant="error">{{ $errors->first() }}</x-alert>@endif

        <x-form-section :title="__('Applicant')" icon="user">
            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                @foreach ([
                    [__('Contact'), $req->contact_name],
                    [__('Email'), $req->email],
                    [__('Phone'), $req->phone ?: '—'],
                    [__('Fleet size'), $req->fleet_size ?: '—'],
                    [__('Plan interested in'), $req->plan?->name ?? '—'],
                    [__('Status'), $req->status->label()],
                ] as [$l, $val])
                    <div><dt class="text-xs font-semibold uppercase tracking-wider text-text-muted">{{ $l }}</dt><dd class="mt-0.5 font-medium text-text-primary dark:text-white">{{ $val }}</dd></div>
                @endforeach
            </dl>
            @if ($req->message)
                <div class="mt-4 border-l-2 border-brand-primary bg-bg p-3 text-sm dark:bg-white/5">{{ $req->message }}</div>
            @endif
        </x-form-section>

        @if ($req->tenant)
            <x-alert variant="success">
                {{ __('Approved — workspace') }} <a class="font-bold underline" href="{{ route('super-admin.tenants.show', $req->tenant) }}">{{ $req->tenant->name }}</a>
                {{ __('was created') }}@if ($req->reviewed_at) {{ __('on :d', ['d' => $req->reviewed_at->format('d M Y')]) }}@endif.
            </x-alert>
        @else
            <x-form-section :title="__('Approve & create workspace')" icon="check-circle" :description="__('Creates the company, its first administrator and a set-password email.')">
                <form method="POST" action="{{ route('super-admin.requests.approve', $req) }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    <x-field name="plan_id" :label="__('Plan')" icon="tag" required>
                        <select id="plan_id" name="plan_id" required>
                            @foreach ($plans as $plan)
                                <option value="{{ $plan->id }}" @selected(old('plan_id', $req->plan_id) == $plan->id)>{{ $plan->name }} @if ($plan->trial_days) — {{ $plan->trial_days }}-day trial @endif</option>
                            @endforeach
                        </select>
                    </x-field>
                    <x-field name="billing_cycle" :label="__('Billing cycle')" icon="calendar" required>
                        <select id="billing_cycle" name="billing_cycle"><option value="monthly">Monthly</option><option value="yearly">Yearly</option></select>
                    </x-field>
                    <x-field name="admin_notes" :label="__('Internal notes')" class="sm:col-span-2">
                        <textarea id="admin_notes" name="admin_notes" rows="2">{{ old('admin_notes', $req->admin_notes) }}</textarea>
                    </x-field>
                    <div class="sm:col-span-2"><button class="btn-primary"><x-icon name="check" :size="16" /> {{ __('Approve & create workspace') }}</button></div>
                </form>
            </x-form-section>

            <x-form-section :title="__('Not a fit?')" icon="x-circle">
                <form method="POST" action="{{ route('super-admin.requests.update', $req) }}" class="flex flex-wrap gap-2">
                    @csrf @method('PUT')
                    <input type="hidden" name="admin_notes" value="{{ $req->admin_notes }}">
                    <button name="status" value="rejected" class="btn-danger btn-sm"><x-icon name="x" :size="14" /> {{ __('Reject & notify') }}</button>
                    <button name="status" value="pending" class="btn-ghost btn-sm">{{ __('Back to pending') }}</button>
                </form>
            </x-form-section>
        @endif

        <form method="POST" action="{{ route('super-admin.requests.destroy', $req) }}">
            @csrf @method('DELETE')
            <button class="btn-ghost btn-sm !text-status-cancelled" data-confirm="Delete this request permanently?"><x-icon name="trash" :size="14" /> {{ __('Delete request') }}</button>
        </form>
    </x-form-page>
</x-app-layout>
