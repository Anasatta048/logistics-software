<x-app-layout>
    <x-slot name="header">{{ __('Plans') }}</x-slot>

    <div class="space-y-5">
        <div class="form-page-head !mb-0 flex-wrap">
            <span class="form-page-icon"><x-icon name="tag" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Subscription plans') }}</h1>
                <p class="text-sm text-text-muted">{{ __('What you offer on the landing page. Inactive plans are hidden from visitors.') }}</p>
            </div>
            <a href="{{ route('super-admin.plans.create') }}" class="btn-primary"><x-icon name="plus" :size="16" /> {{ __('Add plan') }}</a>
        </div>

        @if (session('success'))<x-alert variant="success">{{ session('success') }}</x-alert>@endif

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($plans as $plan)
                <article class="relative border bg-surface p-5 dark:bg-surface-dark {{ $plan->is_featured ? 'border-brand-primary' : 'border-border dark:border-white/10' }} {{ $plan->is_active ? '' : 'opacity-60' }}">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h3 class="text-lg font-extrabold text-text-primary dark:text-white">{{ $plan->name }}</h3>
                            <p class="text-xs text-text-muted">{{ $plan->tagline }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            @if ($plan->is_featured)<span class="bg-brand-primary px-2 py-0.5 text-[10px] font-bold uppercase text-white">{{ __('Featured') }}</span>@endif
                            @unless ($plan->is_active)<span class="bg-border px-2 py-0.5 text-[10px] font-bold uppercase text-text-muted">{{ __('Hidden') }}</span>@endunless
                        </div>
                    </div>
                    <p class="mt-4 text-3xl font-extrabold text-text-primary dark:text-white">${{ number_format($plan->monthly_price, 0) }}<span class="text-sm font-medium text-text-muted"> / {{ __('month') }}</span></p>
                    <p class="text-xs text-text-muted">${{ number_format($plan->yearly_price, 0) }} {{ __('billed yearly') }} · {{ $plan->trial_days }}-{{ __('day trial') }}</p>

                    <dl class="mt-4 grid grid-cols-3 gap-px bg-border text-center text-xs dark:bg-white/10">
                        @foreach ([[__('Users'), $plan->max_users], [__('Drivers'), $plan->max_drivers], [__('Loads/mo'), $plan->max_loads_per_month]] as [$l, $v])
                            <div class="bg-surface py-2 dark:bg-surface-dark"><dd class="font-bold text-text-primary dark:text-white">{{ \App\Models\Plan::limit($v) }}</dd><dt class="text-text-muted">{{ $l }}</dt></div>
                        @endforeach
                    </dl>

                    <p class="mt-4 text-xs text-text-muted">{{ trans_choice(':count request|:count requests', $plan->requests_count) }}</p>

                    <div class="mt-4 flex items-center gap-2 border-t border-border pt-4 dark:border-white/10">
                        <a href="{{ route('super-admin.plans.edit', $plan) }}" class="btn-ghost btn-sm flex-1"><x-icon name="pencil" :size="14" /> {{ __('Edit') }}</a>
                        <form method="POST" action="{{ route('super-admin.plans.destroy', $plan) }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-ghost btn-sm !text-status-cancelled" title="{{ __('Remove') }}" data-confirm="Remove the plan “{{ $plan->name }}”?"><x-icon name="trash" :size="14" /></button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</x-app-layout>
