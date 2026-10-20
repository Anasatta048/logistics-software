<x-app-layout>
    <x-slot name="header">{{ __('Check import') }}</x-slot>

    @php($columns = $definition['columns'])
    <div class="form-page max-w-6xl">
        <div class="form-page-head">
            <span class="form-page-icon"><x-icon name="upload" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Check your :what file', ['what' => strtolower($definition['label'])]) }}</h1>
                <p class="mt-0.5 text-sm text-text-muted">{{ $file['name'] }} · {{ count($file['rows']) }} {{ __('rows') }}. {{ __('Nothing is saved until you press Import.') }}</p>
            </div>
            <a href="{{ route('data.index') }}" class="btn-ghost shrink-0"><x-icon name="arrow-left" :size="15" /> {{ __('Start over') }}</a>
        </div>

        @if ($errors->any())<x-alert variant="error" class="mb-4">{{ $errors->first() }}</x-alert>@endif

        <form method="GET" action="{{ route('data.import.preview', [$entity, $token]) }}" id="mapping-form">
            <x-card class="mb-5">
                <h2 class="font-bold text-text-primary dark:text-white">{{ __('Match your columns') }}</h2>
                <p class="mb-3 text-sm text-text-muted">{{ __('We matched headings by name. Fix any that are wrong. * means required.') }}</p>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($columns as $key => [$label, $required])
                        <div>
                            <label class="text-xs font-semibold text-text-muted">{{ $label }}@if ($required) <span class="text-status-cancelled">*</span>@endif</label>
                            <select name="mapping[{{ $key }}]" class="mt-1 block w-full border-border text-sm {{ $required && ($mapping[$key] ?? null) === null ? 'border-status-cancelled' : '' }}">
                                <option value="">{{ __('— not in my file —') }}</option>
                                @foreach ($file['headers'] as $i => $h)
                                    <option value="{{ $i }}" @selected(($mapping[$key] ?? null) === $i)>{{ $h !== '' ? $h : 'Column '.($i + 1) }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
                <button class="btn-ghost btn-sm mt-4"><x-icon name="refresh" :size="14" /> {{ __('Re-check with these columns') }}</button>
            </x-card>
        </form>

        <div class="mb-5 grid grid-cols-3 gap-3">
            <x-reports.stat :label="__('Rows in file')" :value="count($file['rows'])" />
            <x-reports.stat :label="__('Ready to import')" :value="$check['valid']" tone="good" />
            <x-reports.stat :label="__('Will be skipped')" :value="$check['invalid']" :tone="$check['invalid'] ? 'bad' : null" />
        </div>

        @if ($check['errors'])
            <x-card :padded="false" class="mb-5">
                <h2 class="border-b border-border px-5 py-3 font-bold text-text-primary dark:border-white/10 dark:text-white">{{ __('Problems found') }} @if ($check['invalid'] > count($check['errors']))<span class="text-xs font-normal text-text-muted">· {{ __('first :n shown', ['n' => count($check['errors'])]) }}</span>@endif</h2>
                <ul class="max-h-72 divide-y divide-border/60 overflow-y-auto text-sm dark:divide-white/5">
                    @foreach (array_slice($check['errors'], 0, 50) as $e)
                        <li class="flex gap-3 px-5 py-2"><span class="w-16 shrink-0 text-text-muted">{{ __('Line') }} {{ $e['line'] }}</span><span class="text-status-cancelled">{{ implode(' ', $e['messages']) }}</span></li>
                    @endforeach
                </ul>
            </x-card>
        @endif

        <x-card :padded="false" class="mb-5">
            <h2 class="border-b border-border px-5 py-3 font-bold text-text-primary dark:border-white/10 dark:text-white">{{ __('First rows of your file') }}</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border/60 text-xs dark:divide-white/5">
                    <thead class="text-left uppercase tracking-wider text-text-muted"><tr>@foreach ($file['headers'] as $h)<th class="px-3 py-2">{{ $h }}</th>@endforeach</tr></thead>
                    <tbody class="divide-y divide-border/60 dark:divide-white/5">
                        @foreach (array_slice($file['rows'], 0, 8) as $r)
                            <tr>@foreach ($file['headers'] as $i => $h)<td class="whitespace-nowrap px-3 py-1.5">{{ $r[$i] ?? '' }}</td>@endforeach</tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        <form method="POST" action="{{ route('data.import.commit', [$entity, $token]) }}">
            @csrf
            @foreach ($mapping as $key => $index)
                @if ($index !== null)<input type="hidden" name="mapping[{{ $key }}]" value="{{ $index }}">@endif
            @endforeach
            <button class="btn-primary" @disabled($check['valid'] === 0)><x-icon name="check" :size="16" /> {{ __('Import :n rows', ['n' => $check['valid']]) }}</button>
            @if ($check['invalid'])<span class="ml-3 text-sm text-text-muted">{{ __(':n problem rows will be skipped and listed afterwards.', ['n' => $check['invalid']]) }}</span>@endif
        </form>
    </div>
</x-app-layout>
