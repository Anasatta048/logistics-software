<x-app-layout>
    <x-slot name="header">{{ __('Import finished') }}</x-slot>

    <div class="form-page max-w-3xl">
        <div class="form-page-head">
            <span class="form-page-icon"><x-icon name="check-circle" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Import finished') }}</h1>
                <p class="mt-0.5 text-sm text-text-muted">{{ __($definition['label']) }}</p>
            </div>
        </div>

        <div class="mb-5 grid grid-cols-2 gap-3">
            <x-reports.stat :label="__('Imported')" :value="$result['created']" tone="good" />
            <x-reports.stat :label="__('Skipped')" :value="$result['invalid']" :tone="$result['invalid'] ? 'bad' : null" />
        </div>

        @if ($result['errors'])
            <x-card :padded="false" class="mb-5">
                <h2 class="border-b border-border px-5 py-3 font-bold text-text-primary dark:border-white/10 dark:text-white">{{ __('Rows that were skipped') }}</h2>
                <ul class="max-h-80 divide-y divide-border/60 overflow-y-auto text-sm dark:divide-white/5">
                    @foreach (array_slice($result['errors'], 0, 100) as $e)
                        <li class="flex gap-3 px-5 py-2"><span class="w-16 shrink-0 text-text-muted">{{ __('Line') }} {{ $e['line'] }}</span><span class="text-status-cancelled">{{ implode(' ', $e['messages']) }}</span></li>
                    @endforeach
                </ul>
            </x-card>
            @if ($token)
                <a href="{{ route('data.import.errors', [$entity, $token]) }}" data-no-loader class="btn-ghost"><x-icon name="download" :size="14" /> {{ __('Download skipped rows') }}</a>
            @endif
        @endif

        <div class="mt-6 flex gap-2">
            <a href="{{ route('data.index') }}" class="btn-primary">{{ __('Import another file') }}</a>
        </div>
    </div>
</x-app-layout>
