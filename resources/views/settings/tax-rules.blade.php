<x-app-layout>
    <x-slot name="header">{{ __('Tax rules') }}</x-slot>

    <div class="form-page max-w-4xl">
        <div class="form-page-head">
            <span class="form-page-icon"><x-icon name="percent" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Tax rules') }}</h1>
                <p class="mt-0.5 text-sm text-text-muted">{{ __('Name the taxes deducted when you convert USD to PKR, then pick one on the conversion form instead of typing the rate.') }}</p>
            </div>
            <a href="{{ route('settings.business.edit') }}" class="btn-ghost shrink-0"><x-icon name="arrow-left" :size="15" /> {{ __('Business settings') }}</a>
        </div>

        @if (session('success'))<x-alert variant="success" class="mb-4">{{ session('success') }}</x-alert>@endif
        @if ($errors->any())<x-alert variant="error" class="mb-4">{{ $errors->first() }}</x-alert>@endif

        <form method="POST" action="{{ route('tax-rules.store') }}" class="mb-5 grid gap-2 sm:grid-cols-[1fr_9rem_8rem_auto]">
            @csrf
            <input name="name" required maxlength="80" placeholder="{{ __('e.g. Withholding tax (FBR)') }}" class="border-border text-sm">
            <select name="type" class="border-border text-sm"><option value="percentage">{{ __('Percentage') }}</option><option value="fixed">{{ __('Fixed amount') }}</option></select>
            <input name="value" type="number" step="0.0001" min="0" required placeholder="1" class="border-border text-sm">
            <button class="btn-primary"><x-icon name="plus" :size="16" /> {{ __('Add') }}</button>
        </form>

        <x-card :padded="false">
            @forelse ($rules as $rule)
                <form method="POST" action="{{ route('tax-rules.update', $rule) }}" class="flex flex-wrap items-center gap-3 border-b border-border/60 px-4 py-3 last:border-0 dark:border-white/5 {{ $rule->is_active ? '' : 'opacity-60' }}">
                    @csrf @method('PUT')
                    <input name="name" value="{{ $rule->name }}" required maxlength="80" class="min-w-0 flex-1 border-border text-sm">
                    <select name="type" class="w-36 border-border text-sm"><option value="percentage" @selected($rule->type === 'percentage')>{{ __('Percentage') }}</option><option value="fixed" @selected($rule->type === 'fixed')>{{ __('Fixed amount') }}</option></select>
                    <input name="value" type="number" step="0.0001" min="0" value="{{ (float) $rule->value }}" required class="w-28 border-border text-sm">
                    <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="is_default" value="1" @checked($rule->is_default)> {{ __('Default') }}</label>
                    <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="is_active" value="1" @checked($rule->is_active)> {{ __('Active') }}</label>
                    <button class="btn-ghost btn-sm"><x-icon name="save" :size="14" /></button>
                    <button type="submit" formaction="{{ route('tax-rules.destroy', $rule) }}" name="_method" value="DELETE" class="btn-ghost btn-sm !text-status-cancelled" data-confirm="Remove “{{ $rule->name }}”?"><x-icon name="trash" :size="14" /></button>
                </form>
            @empty
                <p class="px-4 py-4 text-sm text-text-muted">{{ __('No tax rules yet. Conversions can still enter a tax by hand.') }}</p>
            @endforelse
        </x-card>
    </div>
</x-app-layout>
