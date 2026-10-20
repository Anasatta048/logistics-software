<x-app-layout>
    <x-slot name="header">{{ $load->reference_number }}
        <span class="ml-2 align-middle"><x-status-badge :status="$load->status" /></span>
    </x-slot>

    @php
        $field = fn (string $label, $value) => [$label, filled($value) ? $value : '—'];
        $money = fn ($amount) => '$' . number_format((float) $amount, 2);
    @endphp

    <div class="form-page max-w-7xl space-y-5">
        <div class="form-page-head !mb-0 flex-wrap">
            <span class="form-page-icon"><x-icon name="truck" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Load') }} {{ $load->reference_number }}</h1>
                <p class="mt-0.5 text-sm text-text-muted">{{ $load->origin_address }} &rarr; {{ $load->destination_address }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @can('update', $load)
                    <a href="{{ route('loads.edit', $load) }}" class="btn-ghost btn-sm"><x-icon name="pencil" :size="14" /> {{ __('Edit') }}</a>
                @endcan
                @can('cancel', $load)
                    <button type="button" class="btn-danger btn-sm" x-data x-on:click="$dispatch('open-modal', 'cancel-load')">
                        <x-icon name="x-circle" :size="14" /> {{ __('Cancel load') }}
                    </button>
                @endcan
                <a href="{{ route('loads.index') }}" class="btn-ghost btn-sm"><x-icon name="arrow-left" :size="14" /> {{ __('All loads') }}</a>
            </div>
        </div>

        @if (session('success'))
            <x-alert variant="success">{{ session('success') }}</x-alert>
        @endif

        @if ($load->cancelled_at)
            <x-alert variant="danger">
                {{ __('Cancelled') }} <x-datetime :value="$load->cancelled_at" :stacked="false" />
                @if ($load->cancelledBy) {{ __('by :name', ['name' => $load->cancelledBy->name]) }} @endif
                @if ($load->cancellation_reason) &mdash; {{ $load->cancellation_reason }} @endif
            </x-alert>
        @endif

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <x-card class="space-y-3 lg:col-span-2">
                <h3 class="text-sm font-semibold text-text-primary dark:text-white">{{ __('Trip') }}</h3>
                <dl class="grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                    @foreach ([
                        $field(__('Origin'), $load->origin_address),
                        $field(__('Destination'), $load->destination_address),
                        $field(__('Total miles'), $load->total_miles),
                        $field(__('Equipment'), $load->equipment_type),
                        $field(__('Weight (lbs)'), $load->weight_lbs),
                        $field(__('Dimensions (ft)'), collect([$load->length_ft, $load->width_ft, $load->height_ft])->filter()->implode(' × ')),
                    ] as [$label, $value])
                        <div>
                            <dt class="text-xs uppercase tracking-wider text-text-muted">{{ $label }}</dt>
                            <dd class="mt-0.5 text-text-primary dark:text-white">{{ $value }}</dd>
                        </div>
                    @endforeach
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-text-muted">{{ __('Pickup') }}</dt>
                        <dd class="mt-0.5 text-text-primary dark:text-white"><x-datetime :value="$load->pickup_at" /></dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-text-muted">{{ __('Drop-off') }}</dt>
                        <dd class="mt-0.5 text-text-primary dark:text-white">@if ($load->dropoff_at)<x-datetime :value="$load->dropoff_at" />@else — @endif</dd>
                    </div>
                </dl>
            </x-card>

            <x-card class="space-y-3">
                <h3 class="text-sm font-semibold text-text-primary dark:text-white">{{ __('Money') }}</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex items-center justify-between"><dt class="text-text-muted">{{ __('Rate') }}</dt><dd class="font-semibold text-text-primary dark:text-white">{{ $money($load->rate) }}</dd></div>
                    <div class="flex items-center justify-between"><dt class="text-text-muted">{{ __('Rate / mile') }}</dt><dd class="font-semibold text-text-primary dark:text-white">{{ $money($load->rate_per_mile) }}</dd></div>
                    @can('view-company-commission')
                        <div class="flex items-center justify-between border-t border-border/60 pt-3 dark:border-white/10">
                            <dt class="text-text-muted">{{ __('Company commission') }} ({{ rtrim(rtrim((string) $load->company_commission_percentage, '0'), '.') }}%)</dt>
                            <dd class="font-semibold text-status-paid">{{ $money($load->company_commission_amount) }}</dd>
                        </div>
                    @endcan
                </dl>
            </x-card>

            <x-card class="space-y-3 lg:col-span-3">
                <h3 class="text-sm font-semibold text-text-primary dark:text-white">{{ __('People') }}</h3>
                <dl class="grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        $field(__('Company'), $load->company?->name),
                        $field(__('Broker'), $load->broker?->company_name),
                        $field(__('Broker contact'), collect([$load->broker_contact_person, $load->broker_phone, $load->broker_email])->filter()->implode(' · ')),
                        $field(__('Driver'), $load->driverLabel()),
                        $field(__('Dispatcher'), $load->dispatcher?->name),
                    ] as [$label, $value])
                        <div>
                            <dt class="text-xs uppercase tracking-wider text-text-muted">{{ $label }}</dt>
                            <dd class="mt-0.5 text-text-primary dark:text-white">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if (filled($load->notes))
                    <div class="border-t border-border/60 pt-3 text-sm dark:border-white/10">
                        <div class="text-xs uppercase tracking-wider text-text-muted">{{ __('Notes') }}</div>
                        <p class="mt-0.5 whitespace-pre-line text-text-primary dark:text-white">{{ $load->notes }}</p>
                    </div>
                @endif
            </x-card>
        </div>

        <x-card class="space-y-6">
            @include('loads.partials.history', ['load' => $load])
            @include('loads.partials.attachments', ['load' => $load])
        </x-card>
    </div>

    @include('loads.partials.cancel-modal', ['load' => $load])
</x-app-layout>
