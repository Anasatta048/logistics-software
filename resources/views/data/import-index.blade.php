<x-app-layout>
    <x-slot name="header">{{ __('Import & export') }}</x-slot>

    <div class="form-page max-w-5xl">
        <div class="form-page-head">
            <span class="form-page-icon"><x-icon name="upload" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Import & export') }}</h1>
                <p class="mt-0.5 text-sm text-text-muted">{{ __('Bring your old records in from Excel or CSV, and take a full backup out whenever you like.') }}</p>
            </div>
        </div>

        @if (session('success'))<x-alert variant="success" class="mb-4">{{ session('success') }}</x-alert>@endif
        @if ($errors->any())<x-alert variant="error" class="mb-4">{{ $errors->first() }}</x-alert>@endif

        @can('data.import')
        <h2 class="mb-2 text-sm font-semibold uppercase tracking-wider text-text-muted">{{ __('Import from a spreadsheet') }}</h2>
        <p class="mb-3 text-sm text-text-muted">{{ __('Best order: carriers, brokers, drivers, then loads. Download the template, paste your data under its headings, and upload. You will see a check of every row before anything is saved.') }}</p>
        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($entities as $key => $e)
                <x-card>
                    <h3 class="font-bold text-text-primary dark:text-white">{{ __($e['label']) }}</h3>
                    <p class="mt-1 text-sm text-text-muted">{{ __($e['note']) }}</p>
                    <p class="mt-2 text-xs text-text-muted">{{ __('Needs:') }} {{ collect($e['columns'])->filter(fn ($c) => $c[1])->map(fn ($c) => $c[0])->implode(', ') }}</p>
                    <form method="POST" action="{{ route('data.import.upload', $key) }}" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-center gap-2">
                        @csrf
                        <input type="file" name="file" accept=".csv,.xlsx" required class="min-w-0 flex-1 text-sm">
                        <button class="btn-primary btn-sm"><x-icon name="upload" :size="14" /> {{ __('Check file') }}</button>
                    </form>
                    <a href="{{ route('data.import.template', $key) }}" data-no-loader class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-brand-primary"><x-icon name="download" :size="12" /> {{ __('Download template') }}</a>
                </x-card>
            @endforeach
        </div>

        @endcan

        @can('data.export')
        <h2 class="mb-2 mt-10 text-sm font-semibold uppercase tracking-wider text-text-muted">{{ __('Back up everything') }}</h2>
        <x-card>
            <p class="text-sm text-text-muted">{{ __('A ZIP with one spreadsheet-ready CSV per area: carriers, drivers, brokers, loads, invoices, payments, expenses, accounts and ledger, vouchers, commissions, settings. Open the files in Excel.') }}</p>
            <form method="POST" action="{{ route('data.export.store') }}" class="mt-3">
                @csrf
                <button class="btn-primary"><x-icon name="download" :size="16" /> {{ __('Prepare a backup') }}</button>
            </form>

            @if ($exports->isNotEmpty())
                <ul class="mt-4 divide-y divide-border/60 border-t border-border/60 dark:divide-white/5 dark:border-white/10">
                    @foreach ($exports as $x)
                        <li class="flex items-center gap-3 py-2.5 text-sm">
                            <span class="w-40 text-text-muted">{{ $x->created_at->format(df().' H:i') }}</span>
                            <span class="flex-1">
                                @if ($x->status === 'ready')<span class="font-semibold text-status-paid">{{ __('Ready') }}</span> <span class="text-text-muted">· {{ number_format($x->size / 1024, 0) }} KB</span>
                                @elseif ($x->status === 'failed')<span class="font-semibold text-status-cancelled">{{ __('Failed') }}</span>
                                @else<span class="font-semibold text-brand-gold">{{ __('Preparing…') }}</span>@endif
                            </span>
                            @if ($x->status === 'ready')
                                <a href="{{ route('data.export.download', $x) }}" data-no-loader class="btn-ghost btn-sm"><x-icon name="download" :size="14" /> {{ __('Download') }}</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
        @endcan
    </div>
</x-app-layout>
