<x-app-layout>
    <x-slot name="header">{{ __('Accounts') }}</x-slot>

    <div class="space-y-6">
        <div class="form-page-head !mb-0 flex-wrap">
            <span class="form-page-icon"><x-icon name="layers" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Accounts') }}</h1>
                <p class="text-sm text-text-muted">{{ __('Cash and bank accounts, with their running balances.') }}</p>
            </div>
            @can('accounts.transfer')
                <a href="{{ route('account-transfers.create') }}" class="btn-ghost"><x-icon name="repeat" :size="16" /> {{ __('Transfer') }}</a>
            @endcan
            @can('reports.fundsFlow')
                <a href="{{ route('reports.funds-flow') }}" class="btn-ghost"><x-icon name="chart-bar" :size="16" /> {{ __('Funds flow') }}</a>
            @endcan
            @can('create', App\Models\Account::class)
                <x-button variant="primary" as="a" href="{{ route('accounts.create') }}">
                    <x-icon name="plus" :size="16" /> {{ __('Add Account') }}
                </x-button>
            @endcan
        </div>

        @if (session('success'))
            <x-alert variant="success">{{ session('success') }}</x-alert>
        @endif

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-3">
            <x-card class="px-4 py-3"><div class="text-xs uppercase tracking-wider text-text-muted">{{ __('Cash in hand') }}</div><div class="text-xl font-bold text-text-primary dark:text-white">{{ cur_local() }} {{ number_format($cash, 2) }}</div></x-card>
            <x-card class="px-4 py-3"><div class="text-xs uppercase tracking-wider text-text-muted">{{ __('In banks') }}</div><div class="text-xl font-bold text-text-primary dark:text-white">{{ cur_local() }} {{ number_format($bank, 2) }}</div></x-card>
            <x-card class="col-span-2 px-4 py-3 lg:col-span-1"><div class="text-xs uppercase tracking-wider text-text-muted">{{ __('Total money') }}</div><div class="text-xl font-bold text-brand-primary">{{ cur_local() }} {{ number_format($cash + $bank, 2) }}</div></x-card>
        </div>

        <x-card :padded="false">
            @if ($accounts->isEmpty())
                <x-empty-state icon="wallet" title="No accounts found" description="Add your first cash or bank account.">
                    <x-slot name="action">
                        @can('create', App\Models\Account::class)
                            <x-button variant="primary" as="a" href="{{ route('accounts.create') }}">{{ __('Add Account') }}</x-button>
                        @endcan
                    </x-slot>
                </x-empty-state>
            @else
                <div class="overflow-x-auto">
                    <table data-table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr>
                                <th class="px-4 py-3">Name</th>
                                <th data-filter="select" class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Opening Balance</th>
                                <th class="px-4 py-3">Current Balance</th>
                                <th data-filter="select" class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($accounts as $account)
                                <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 font-medium text-text-primary dark:text-white">{{ $account->name }}</td>
                                    <td class="px-4 py-3">{{ $account->type->label() }}</td>
                                    <td class="px-4 py-3">{{ number_format((float) $account->opening_balance, 2) }}</td>
                                    <td class="px-4 py-3">{{ number_format((float) $account->current_balance, 2) }}</td>
                                    <td class="px-4 py-3"><x-status-badge :status="$account->status" /></td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('accounts.ledger', $account) }}" class="text-text-muted hover:text-brand-primary text-xs font-medium">{{ __('Ledger') }}</a>
                                            @can('update', $account)
                                                <a href="{{ route('accounts.edit', $account) }}" class="text-text-muted hover:text-brand-primary" title="{{ __('Edit') }}">
                                                    <x-icon name="pencil" :size="16" />
                                                </a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        <div>{{ $accounts->links() }}</div>
    </div>
</x-app-layout>
