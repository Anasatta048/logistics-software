<x-app-layout>
    <x-slot name="header">{{ __('Journal Vouchers') }}</x-slot>

    <div class="space-y-6">
        <div class="form-page-head !mb-0 flex-wrap">
            <span class="form-page-icon"><x-icon name="note" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Journal Vouchers') }}</h1>
                <p class="text-sm text-text-muted">{{ __('Manual debit/credit entries between accounts.') }}</p>
            </div>
            @can('create', App\Models\JournalVoucher::class)
                <x-button variant="primary" as="a" href="{{ route('journal-vouchers.create') }}">
                    <x-icon name="plus" :size="16" /> {{ __('New Voucher') }}
                </x-button>
            @endcan
        </div>

        @if (session('success'))
            <x-alert variant="success">{{ session('success') }}</x-alert>
        @endif

        <x-card :padded="false">
            @if ($vouchers->isEmpty())
                <x-empty-state icon="file-text" title="No journal vouchers found" description="Create your first voucher.">
                    <x-slot name="action">
                        @can('create', App\Models\JournalVoucher::class)
                            <x-button variant="primary" as="a" href="{{ route('journal-vouchers.create') }}">{{ __('New Voucher') }}</x-button>
                        @endcan
                    </x-slot>
                </x-empty-state>
            @else
                <div class="overflow-x-auto">
                    <table data-table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr>
                                <th class="px-4 py-3">JV #</th>
                                <th data-filter="date" class="px-4 py-3">Date</th>
                                <th data-filter="select" class="px-4 py-3">Debit</th>
                                <th data-filter="select" class="px-4 py-3">Credit</th>
                                <th class="px-4 py-3">Amount</th>
                                <th data-filter="select" class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($vouchers as $jv)
                                <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 font-medium text-text-primary dark:text-white">{{ $jv->jv_number }}</td>
                                    <td class="px-4 py-3" data-date="{{ $jv->date?->format('Y-m-d') }}">{{ $jv->date?->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3">{{ $jv->debitAccount->name }}</td>
                                    <td class="px-4 py-3">{{ $jv->creditAccount->name }}</td>
                                    <td class="px-4 py-3">{{ number_format((float) $jv->amount, 2) }}</td>
                                    <td class="px-4 py-3"><x-status-badge :status="$jv->status" /></td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-3">
                                            @can('approve', $jv)
                                                <form method="POST" action="{{ route('journal-vouchers.approve', $jv) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-status-paid hover:underline text-xs font-medium">{{ __('Approve') }}</button>
                                                </form>
                                            @endcan
                                            @can('void', $jv)
                                                <form method="POST" action="{{ route('journal-vouchers.void', $jv) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-status-cancelled hover:underline text-xs font-medium"
                                                        data-confirm="Void this voucher?">{{ __('Void') }}</button>
                                                </form>
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

        <div>{{ $vouchers->links() }}</div>
    </div>
</x-app-layout>
