<x-app-layout>
    <x-slot name="header">{{ $tenant->name }}</x-slot>

    <x-form-page :title="$tenant->name" :subtitle="$tenant->slug" icon="building" :back="route('super-admin.tenants.index')" :back-label="__('All workspaces')" wide>
        @if (session('success'))<x-alert variant="success">{{ session('success') }}</x-alert>@endif
        @if (session('setup_url'))
            <x-alert variant="success">
                <p class="font-semibold">{{ __('Setup link for the new administrator (valid 60 minutes):') }}</p>
                <input type="text" readonly value="{{ session('setup_url') }}" onclick="this.select()" class="mt-2 w-full border-border bg-white text-xs text-text-primary">
            </x-alert>
        @endif
        @if ($errors->any())<x-alert variant="error">{{ $errors->first() }}</x-alert>@endif

        <div class="grid gap-5 lg:grid-cols-3">
            <x-card class="lg:col-span-2">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="px-2.5 py-1 text-xs font-bold {{ $tenant->status->color() }}">{{ $tenant->status->label() }}</span>
                    @if ($tenant->endsAt())
                        <span class="text-sm text-text-muted">{{ $tenant->status->value === 'trial' ? __('Trial ends') : __('Paid until') }} <strong class="text-text-primary dark:text-white">{{ $tenant->endsAt()->format(df()) }}</strong></span>
                    @endif
                    @if ($tenant->suspended_reason)<span class="text-sm text-warning">{{ $tenant->suspended_reason }}</span>@endif
                    <form method="POST" action="{{ route('super-admin.tenants.enter', $tenant) }}" class="ml-auto">
                        @csrf
                        <button class="btn-primary btn-sm"><x-icon name="eye" :size="14" /> {{ __('Enter workspace') }}</button>
                    </form>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                    @foreach ([['users', __('Users'), 'users'], ['drivers', __('Drivers'), 'truck'], ['loads', __('Loads this month'), 'package']] as [$key, $label, $icon])
                        @php($u = $usage[$key])
                        <div class="border border-border p-3 dark:border-white/10">
                            <p class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-text-muted"><x-icon :name="$icon" :size="14" /> {{ $label }}</p>
                            <p class="mt-1 text-2xl font-extrabold text-text-primary dark:text-white">{{ number_format($u['used']) }}<span class="text-sm font-medium text-text-muted"> / {{ \App\Models\Plan::limit($u['limit']) }}</span></p>
                            @if ($u['limit'])
                                <div class="mt-2 h-1.5 bg-border dark:bg-white/10"><div class="h-full {{ $u['used'] >= $u['limit'] ? 'bg-status-cancelled' : 'bg-brand-primary' }}" style="width: {{ min(100, $u['used'] / $u['limit'] * 100) }}%"></div></div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <form method="POST" action="{{ route('super-admin.tenants.update', $tenant) }}" class="mt-6 grid gap-4 sm:grid-cols-2">
                    @csrf @method('PUT')
                    <x-field name="name" :label="__('Name')" icon="building" required class="sm:col-span-2"><input id="name" name="name" type="text" value="{{ old('name', $tenant->name) }}" required></x-field>
                    <x-field name="plan_id" :label="__('Plan')" icon="tag" required>
                        <select id="plan_id" name="plan_id">
                            @foreach ($plans as $plan)<option value="{{ $plan->id }}" @selected(old('plan_id', $tenant->plan_id) == $plan->id)>{{ $plan->name }}</option>@endforeach
                        </select>
                    </x-field>
                    <x-field name="billing_cycle" :label="__('Billing cycle')" icon="calendar" required>
                        <select id="billing_cycle" name="billing_cycle">
                            @foreach (['monthly', 'yearly'] as $cycle)<option value="{{ $cycle }}" @selected(old('billing_cycle', $tenant->billing_cycle) === $cycle)>{{ ucfirst($cycle) }}</option>@endforeach
                        </select>
                    </x-field>
                    <x-field name="notes" :label="__('Internal notes')" class="sm:col-span-2"><textarea id="notes" name="notes" rows="2">{{ old('notes', $tenant->notes) }}</textarea></x-field>
                    <div class="sm:col-span-2"><button class="btn-primary"><x-icon name="save" :size="16" /> {{ __('Save') }}</button></div>
                </form>
            </x-card>

            <div class="space-y-5">
                <x-card>
                    <h3 class="font-bold text-text-primary dark:text-white">{{ __('Payments') }}</h3>
                    <ul class="mt-3 space-y-2 text-sm">
                        @forelse ($payments as $p)
                            <li class="flex items-center justify-between gap-2"><span>{{ $p->created_at->format(df()) }} · {{ $p->money() }}<br><span class="text-xs text-text-muted">{{ $p->receipt_number ?? ucfirst($p->status) }}</span></span>
                                <span class="text-xs text-text-muted">{{ __($p->methodLabel()) }}</span></li>
                        @empty
                            <li class="text-text-muted">{{ __('No payments yet.') }}</li>
                        @endforelse
                    </ul>
                </x-card>

                <x-card>
                    <h3 class="font-bold text-text-primary dark:text-white">{{ __('Billing & access') }}</h3>
                    <div class="mt-3 space-y-3 text-sm">
                        <form method="POST" action="{{ route('super-admin.tenants.activate', $tenant) }}">
                            @csrf
                            <button class="btn-primary w-full" data-confirm="Record a payment and extend by one {{ $tenant->billing_cycle === 'yearly' ? 'year' : 'month' }}?"><x-icon name="check-circle" :size="16" /> {{ __('Record payment / reactivate') }}</button>
                        </form>
                        <form method="POST" action="{{ route('super-admin.tenants.extend-trial', $tenant) }}" class="flex gap-2">
                            @csrf
                            <input type="number" name="days" min="1" max="365" value="7" class="w-20 border-border text-sm">
                            <button class="btn-ghost btn-sm flex-1">{{ __('Extend trial (days)') }}</button>
                        </form>
                        <form method="POST" action="{{ route('super-admin.tenants.suspend', $tenant) }}" class="space-y-2">
                            @csrf
                            <input type="text" name="reason" maxlength="190" placeholder="{{ __('Reason shown to the customer') }}" class="w-full border-border text-sm">
                            <button class="btn-ghost btn-sm w-full !text-warning" data-confirm="Suspend {{ $tenant->name }}? Its users will be locked out.">{{ __('Suspend workspace') }}</button>
                        </form>
                        <form method="POST" action="{{ route('super-admin.tenants.cancel', $tenant) }}">
                            @csrf
                            <button class="btn-ghost btn-sm w-full !text-status-cancelled" data-confirm="Cancel {{ $tenant->name }}? Data is kept.">{{ __('Cancel workspace') }}</button>
                        </form>
                    </div>
                </x-card>

                <x-card>
                    <h3 class="font-bold text-text-primary dark:text-white">{{ __('Contact') }}</h3>
                    <p class="mt-2 text-sm">{{ $tenant->contact_name }}<br><span class="text-text-muted">{{ $tenant->email }}<br>{{ $tenant->phone }}</span></p>
                </x-card>
            </div>
        </div>

        <x-card :padded="false">
            <h3 class="border-b border-border px-5 py-3 font-bold text-text-primary dark:border-white/10 dark:text-white">{{ __('Recent platform activity') }}</h3>
            @forelse ($logs as $log)
                <div class="flex items-center gap-3 border-b border-border/60 px-5 py-2.5 text-sm last:border-0 dark:border-white/5">
                    <span class="w-36 shrink-0 text-xs text-text-muted">{{ $log->created_at->format('d M Y H:i') }}</span>
                    <span class="flex-1">{{ $log->description ?: $log->action }}</span>
                    <span class="text-xs text-text-muted">{{ $log->user?->name }}</span>
                </div>
            @empty
                <p class="px-5 py-4 text-sm text-text-muted">{{ __('Nothing yet.') }}</p>
            @endforelse
        </x-card>
    </x-form-page>
</x-app-layout>
