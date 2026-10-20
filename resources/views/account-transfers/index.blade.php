<x-app-layout>
    <x-slot name="header">{{ __('Transfers') }}</x-slot>

    <div class="space-y-6">
        <div class="form-page-head !mb-0 flex-wrap">
            <span class="form-page-icon"><x-icon name="repeat" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Transfers') }}</h1>
                <p class="text-sm text-text-muted">{{ __('Money moved between your own accounts: cash to bank, bank to bank, wallet to bank.') }}</p>
            </div>
            @can('accounts.transfer')
                <a href="{{ route('account-transfers.create') }}" class="btn-primary"><x-icon name="plus" :size="16" /> {{ __('New transfer') }}</a>
            @endcan
        </div>

        @if (session('success'))<x-alert variant="success">{{ session('success') }}</x-alert>@endif

        <x-card :padded="false">
            @if ($transfers->isEmpty())
                <x-empty-state icon="repeat" title="No transfers yet" description="Record a transfer when you move money from one account to another." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">From</th><th class="px-4 py-3">To</th><th class="px-4 py-3 text-right">Amount</th><th class="px-4 py-3 text-right">Charges</th><th class="px-4 py-3">Reference</th><th class="px-4 py-3">By</th><th class="px-4 py-3"></th></tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($transfers as $t)
                                <tr class="hover:bg-bg/60 dark:hover:bg-white/5 {{ $t->isReversed() ? 'opacity-50' : '' }}">
                                    <td class="whitespace-nowrap px-4 py-3">{{ $t->transfer_date->format(df()) }}</td>
                                    <td class="px-4 py-3 font-medium text-text-primary dark:text-white">{{ $t->from->name }}</td>
                                    <td class="px-4 py-3 font-medium text-text-primary dark:text-white">{{ $t->to->name }}</td>
                                    <td class="px-4 py-3 text-right {{ $t->isReversed() ? 'line-through' : '' }}">{{ cur_local() }} {{ number_format((float) $t->amount, 2) }}</td>
                                    <td class="px-4 py-3 text-right text-text-muted">{{ (float) $t->fee > 0 ? cur_local().' '.number_format((float) $t->fee, 2) : '—' }}</td>
                                    <td class="px-4 py-3 text-text-muted">{{ $t->reference ?: '—' }}@if ($t->narration)<span class="block max-w-xs truncate text-xs">{{ $t->narration }}</span>@endif</td>
                                    <td class="px-4 py-3 text-text-muted">{{ $t->author?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right">
                                        @if ($t->isReversed())
                                            <span class="text-xs font-semibold text-status-cancelled">{{ __('Reversed') }}</span>
                                        @else
                                            @can('accounts.transfer')
                                                <form method="POST" action="{{ route('account-transfers.reverse', $t) }}">
                                                    @csrf
                                                    <button class="text-xs font-semibold text-text-muted hover:text-status-cancelled" data-confirm="Reverse transfer #{{ $t->id }}? The money goes back to {{ $t->from->name }}.">{{ __('Reverse') }}</button>
                                                </form>
                                            @endcan
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        <div>{{ $transfers->links() }}</div>
    </div>
</x-app-layout>
