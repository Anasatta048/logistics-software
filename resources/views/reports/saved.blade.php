<x-app-layout>
    <x-slot name="header">{{ __('Saved reports') }}</x-slot>

    <div class="form-page max-w-5xl">
        <div class="form-page-head">
            <span class="form-page-icon"><x-icon name="chart-bar" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Saved reports') }}</h1>
                <p class="mt-0.5 text-sm text-text-muted">{{ __('Reports you saved with their filters. Choose a schedule and we email the file for you, e.g. profit and loss every Monday.') }}</p>
            </div>
            <a href="{{ route('reports.index') }}" class="btn-ghost shrink-0"><x-icon name="arrow-left" :size="15" /> {{ __('All reports') }}</a>
        </div>

        @if (session('success'))<x-alert variant="success" class="mb-4">{{ session('success') }}</x-alert>@endif
        @if ($errors->any())<x-alert variant="error" class="mb-4">{{ $errors->first() }}</x-alert>@endif

        <x-card :padded="false">
            @forelse ($saved as $s)
                <form method="POST" action="{{ route('saved-reports.update', $s) }}" x-data="{ schedule: '{{ $s->schedule }}' }" class="grid gap-3 border-b border-border/60 px-4 py-3 last:border-0 sm:grid-cols-6 dark:border-white/5">
                    @csrf @method('PUT')
                    <div class="sm:col-span-2">
                        <input name="name" value="{{ $s->name }}" required maxlength="80" class="w-full border-border text-sm">
                        <a href="{{ route($s->report, $s->filters ?? []) }}" class="mt-1 inline-block text-xs font-semibold text-brand-primary">{{ __('Open') }} · {{ str($s->report)->after('reports.')->headline() }}</a>
                    </div>
                    <select name="schedule" x-model="schedule" class="border-border text-sm">
                        <option value="none">{{ __('No email') }}</option><option value="weekly">{{ __('Weekly') }}</option><option value="monthly">{{ __('Monthly') }}</option>
                    </select>
                    <div x-show="schedule !== 'none'" x-cloak class="flex gap-2">
                        <select name="schedule_day" x-show="schedule === 'weekly'" class="border-border text-sm">
                            @foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $i => $day)<option value="{{ $i + 1 }}" @selected($s->schedule_day === $i + 1)>{{ $day }}</option>@endforeach
                        </select>
                        <input type="number" name="schedule_day" min="1" max="28" value="{{ $s->schedule_day }}" x-show="schedule === 'monthly'" class="w-20 border-border text-sm" title="{{ __('Day of month') }}">
                        <select name="format" class="border-border text-sm"><option value="csv" @selected($s->format === 'csv')>CSV</option><option value="pdf" @selected($s->format === 'pdf')>PDF</option></select>
                    </div>
                    <input name="recipients" value="{{ $s->recipients }}" x-show="schedule !== 'none'" x-cloak placeholder="{{ __('Emails, separated by commas') }}" class="border-border text-sm sm:col-span-1">
                    <div class="flex items-center justify-end gap-2 sm:col-start-6">
                        @if ($s->last_sent_at)<span class="text-xs text-text-muted">{{ __('Sent') }} {{ $s->last_sent_at->format(df()) }}</span>@endif
                        <button class="btn-ghost btn-sm"><x-icon name="save" :size="14" /></button>
                        <button type="submit" formaction="{{ route('saved-reports.destroy', $s) }}" name="_method" value="DELETE" class="btn-ghost btn-sm !text-status-cancelled" data-confirm="Remove “{{ $s->name }}”?"><x-icon name="trash" :size="14" /></button>
                    </div>
                </form>
            @empty
                <p class="px-4 py-5 text-sm text-text-muted">{{ __('Nothing saved yet. Open any report, set its filters and use “Save this view”.') }}</p>
            @endforelse
        </x-card>
    </div>
</x-app-layout>
