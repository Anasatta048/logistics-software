<x-app-layout>
    <x-slot name="header">{{ __('Commission slabs') }}</x-slot>

    <div class="form-page max-w-3xl">
        <div class="form-page-head">
            <span class="form-page-icon"><x-icon name="layers" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Commission slabs') }}</h1>
                <p class="mt-0.5 text-sm text-text-muted">{{ __('Reward volume: the more margin a dispatcher earns in a month, the higher the rate on the next slice.') }}</p>
            </div>
            <a href="{{ route('settings.business.edit', 'finance') }}" class="btn-ghost shrink-0"><x-icon name="arrow-left" :size="15" /> {{ __('Finance settings') }}</a>
        </div>

        @if (session('success'))<x-alert variant="success" class="mb-4">{{ session('success') }}</x-alert>@endif
        @if ($errors->any())<x-alert variant="error" class="mb-4">{{ $errors->first() }}</x-alert>@endif

        <x-alert :variant="$active ? 'success' : 'info'" class="mb-4">
            {{ $active ? __('Slabs are ON: percentage dispatchers earn by this ladder.') : __('Slabs are OFF. Set "Dispatcher commission" to Slabs in Finance settings to use them.') }}
        </x-alert>

        <form method="POST" action="{{ route('commission-tiers.update') }}" x-data="{ rows: @js($tiers->map(fn ($t) => ['from' => (float) $t->from_amount, 'rate' => (float) $t->rate])->values()) }" class="space-y-4">
            @csrf @method('PUT')
            <x-card :padded="false">
                <div class="hidden grid-cols-[1fr_1fr_auto] gap-3 border-b border-border px-4 py-2 text-xs font-semibold uppercase tracking-wider text-text-muted sm:grid dark:border-white/10"><span>{{ __('Margin from (this month)') }}</span><span>{{ __('Commission %') }}</span><span></span></div>
                <template x-for="(row, i) in rows" :key="i">
                    <div class="grid grid-cols-1 items-center gap-3 border-b border-border/60 px-4 py-3 last:border-0 sm:grid-cols-[1fr_1fr_auto] dark:border-white/5">
                        <input type="number" step="0.01" min="0" :name="`tiers[${i}][from]`" x-model="row.from" :readonly="i === 0" class="border-border text-sm" required>
                        <input type="number" step="0.01" min="0" max="100" :name="`tiers[${i}][rate]`" x-model="row.rate" class="border-border text-sm" required>
                        <button type="button" class="btn-ghost btn-sm !text-status-cancelled" x-show="i > 0" @click="rows.splice(i, 1)"><x-icon name="trash" :size="14" /></button>
                    </div>
                </template>
                <p class="px-4 py-3 text-sm text-text-muted" x-show="rows.length === 0">{{ __('No slabs yet.') }}</p>
            </x-card>
            <div class="flex flex-wrap gap-2">
                <button type="button" class="btn-ghost" x-show="rows.length < 8" @click="rows.push({ from: rows.length ? Number(rows[rows.length - 1].from) + 5000 : 0, rate: 10 })"><x-icon name="plus" :size="14" /> {{ __('Add a slab') }}</button>
                <button class="btn-primary"><x-icon name="save" :size="16" /> {{ __('Save slabs') }}</button>
            </div>
            <p class="text-xs text-text-muted">{{ __('Example: 0 → 10 %, 5,000 → 12 %. A dispatcher who already has 4,000 margin this month and books a load worth 2,000 earns 1,000 × 10 % + 1,000 × 12 % = 220.') }}</p>
        </form>
    </div>
</x-app-layout>
