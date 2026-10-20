<div>
    <h3 class="text-sm font-semibold text-text-primary dark:text-white mb-3">{{ __('Status History') }}</h3>

    <div class="overflow-x-auto rounded-input border border-border/60 dark:border-white/10">
        <table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
            <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                <tr>
                    <th class="px-3 py-2">{{ __('When') }}</th>
                    <th class="px-3 py-2">{{ __('From') }}</th>
                    <th class="px-3 py-2">{{ __('To') }}</th>
                    <th class="px-3 py-2">{{ __('By') }}</th>
                    <th class="px-3 py-2">{{ __('Reason') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border/60 dark:divide-white/5">
                @forelse ($load->statusHistory->sortByDesc('created_at') as $entry)
                    <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                        <td class="px-3 py-2 whitespace-nowrap"><x-datetime :value="$entry->created_at" /></td>
                        <td class="px-3 py-2">{{ $entry->from_status?->label() ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $entry->to_status->label() }}</td>
                        <td class="px-3 py-2">{{ $entry->changedBy->name ?? '—' }}</td>
                        <td class="px-3 py-2 text-text-muted">{{ $entry->reason ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-3 py-4 text-text-muted" colspan="5">{{ __('No history yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
