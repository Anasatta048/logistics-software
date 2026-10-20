<div class="space-y-4">
    <h3 class="text-sm font-semibold text-text-primary dark:text-white">{{ __('Attachments') }}</h3>

    <ul class="text-sm text-text-primary dark:text-white space-y-1">
        @forelse ($load->attachments->sortByDesc('created_at') as $attachment)
            <li>
                <x-status-badge :status="$attachment->type" />
                <a href="{{ route('loads.attachments.download', [$load, $attachment]) }}" data-no-loader
                    class="inline-flex items-center gap-1 font-medium text-brand-primary hover:underline">
                    <x-icon name="download" :size="14" /> {{ __('Download') }} .{{ pathinfo($attachment->file_path, PATHINFO_EXTENSION) }}
                </a>
                <span class="text-text-muted">
                    — {{ $attachment->uploader->name ?? '—' }}, <x-datetime :value="$attachment->created_at" :stacked="false" />
                </span>
            </li>
        @empty
            <li class="text-text-muted">{{ __('No documents attached.') }}</li>
        @endforelse
    </ul>

    @can('update', $load)
        <form method="POST" action="{{ route('loads.attachments.store', $load) }}"
            enctype="multipart/form-data"
            class="space-y-3 border-t border-border/60 dark:border-white/10 pt-4">
            @csrf
            <x-attachment-dropzone />
            <x-button variant="primary" type="submit">{{ __('Upload') }}</x-button>
        </form>
    @endcan
</div>
