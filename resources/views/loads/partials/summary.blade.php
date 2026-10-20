@php
    $submitLabel ??= __('Save & Book Load');
    $rateValue = old('rate', $load?->rate);
    $milesValue = old('total_miles', $load?->total_miles);
    $percentageValue = $load?->company_commission_percentage;
    $ratePerMile = ($rateValue && $milesValue) ? number_format((float) $rateValue / (float) $milesValue, 2) : null;
    // Company commission is an earning figure; only people with accounting rights see it.
    $commission = ($rateValue !== null && $percentageValue !== null)
        ? (float) $rateValue * (float) $percentageValue / 100
        : null;
@endphp
<x-card class="border-t-4 !border-t-brand-primary">
    <h3 class="text-sm font-semibold text-text-primary dark:text-white">{{ __('Summary') }}</h3>
    <p class="mt-1 text-xs text-text-muted">{{ __('Live estimate — recalculated as you type. The server confirms the final numbers on save.') }}</p>

    <dl class="mt-4 space-y-3 text-sm">
        <div class="flex items-center justify-between">
            <dt class="text-text-muted">{{ __('Rate') }}</dt>
            <dd class="font-semibold text-text-primary dark:text-white" data-summary-field="rate">
                {{ $rateValue ? '$' . number_format((float) $rateValue, 2) : '—' }}
            </dd>
        </div>
        <div class="flex items-center justify-between">
            <dt class="text-text-muted">{{ __('Rate / Mile') }}</dt>
            <dd class="font-semibold text-text-primary dark:text-white" data-summary-field="rate-per-mile">
                {{ $ratePerMile ? '$' . $ratePerMile . ' / mi' : '—' }}
            </dd>
        </div>
        @can('view-company-commission')
        <div class="border-t border-border/60 dark:border-white/10 pt-3 flex items-center justify-between">
            <dt class="text-text-muted">{{ __('Company Commission') }}</dt>
            <dd class="font-semibold text-status-paid" data-summary-field="margin">
                {{ $commission !== null ? '$' . number_format($commission, 2) : '—' }}
            </dd>
        </div>
        <div class="flex items-center justify-between">
            <dt class="text-text-muted">{{ __('Commission / Mile') }}</dt>
            <dd class="font-semibold text-text-primary dark:text-white" data-summary-field="margin-per-mile">
                {{ ($commission !== null && $milesValue) ? '$' . number_format($commission / (float) $milesValue, 2) . ' / mi' : '—' }}
            </dd>
        </div>
        @endcan
    </dl>

    <div class="mt-6">
        <x-button type="submit" variant="primary" class="w-full justify-center">
            <x-icon name="check-circle" :size="16" /> {{ $submitLabel }}
        </x-button>
        <a href="{{ route('loads.index') }}" class="mt-3 block text-center text-sm text-text-muted hover:underline">
            {{ __('Cancel') }}
        </a>
    </div>
</x-card>
