<x-app-layout>
    <x-slot name="header">{{ __('Company Payments') }}</x-slot>

    <div class="space-y-6">
        <div class="form-page-head !mb-0 flex-wrap">
            <span class="form-page-icon"><x-icon name="file-text" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Company Payments') }}</h1>
                <p class="text-sm text-text-muted">{{ __('Each payment starts as an invoice for a driver\'s loads (Pending) and is settled as the company pays.') }}</p>
            </div>
            @can('create', App\Models\Invoice::class)
                <x-button variant="primary" as="a" href="{{ route('invoices.create') }}">
                    <x-icon name="plus" :size="16" /> {{ __('New Payment / Invoice') }}
                </x-button>
            @endcan
        </div>

        @if (session('success'))
            <x-alert variant="success">{{ session('success') }}</x-alert>
        @endif

        <x-card :padded="false">
            @if ($invoices->isEmpty())
                <x-empty-state icon="file-text" title="No invoices yet" description="Pick a driver and a date range to bill their company's commission.">
                    <x-slot name="action">
                        @can('create', App\Models\Invoice::class)
                            <x-button variant="primary" as="a" href="{{ route('invoices.create') }}">{{ __('New Invoice') }}</x-button>
                        @endcan
                    </x-slot>
                </x-empty-state>
            @else
                <div class="overflow-x-auto">
                    <table data-table class="min-w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr>
                                <th class="px-4 py-3">#</th>
                                <th data-filter="date" class="px-4 py-3">Date</th>
                                <th data-filter="select" class="px-4 py-3">Driver</th>
                                <th data-filter="select" class="px-4 py-3">Company</th>
                                <th class="px-4 py-3">Period</th>
                                <th class="px-4 py-3 text-right">Loads</th>
                                <th class="px-4 py-3 text-right">Total due</th>
                                <th class="px-4 py-3 text-right">Received</th>
                                <th data-filter="select" class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($invoices as $invoice)
                                <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 font-semibold text-text-primary dark:text-white">
                                        <a href="{{ route('invoices.show', $invoice) }}" class="hover:text-brand-primary">#{{ $invoice->invoice_number }}</a>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap" data-date="{{ $invoice->invoice_date->toDateString() }}">{{ $invoice->invoice_date->format('M j, Y') }}</td>
                                    <td class="px-4 py-3">{{ $invoice->driver_name }}</td>
                                    <td class="px-4 py-3">
                                        {{ $invoice->company->name }}
                                        <span class="block text-xs text-text-muted">MC {{ $invoice->company->mc_number }}</span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-text-muted">
                                        {{ $invoice->period_from->format('M j') }} – {{ $invoice->period_to->format('M j, Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-right">{{ $invoice->lines_count }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-text-primary dark:text-white">${{ number_format($invoice->due(), 2) }}</td>
                                    <td class="px-4 py-3 text-right text-text-muted">${{ number_format((float) $invoice->paid_sum, 2) }}</td>
                                    <td class="px-4 py-3">
                                        @if ($invoice->isCarried())
                                            <span class="lookup-badge">{{ __('Carried to #:n', ['n' => $invoice->carriedTo?->invoice_number]) }}</span>
                                        @else
                                            <x-status-badge :status="$invoice->status()" />
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('invoices.show', $invoice) }}" class="text-text-muted hover:text-brand-primary" title="{{ __('View') }}">
                                                <x-icon name="eye" :size="16" />
                                            </a>
                                            <a href="{{ route('invoices.pdf', $invoice) }}" class="text-xs font-semibold text-brand-primary hover:underline">PDF</a>
                                            @can('delete', $invoice)
                                                <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-text-muted hover:text-status-cancelled" title="{{ __('Delete') }}"
                                                        data-confirm="Delete this invoice? Its loads can be invoiced again.">
                                                        <x-icon name="trash" :size="16" />
                                                    </button>
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

        <div>{{ $invoices->links() }}</div>
    </div>
</x-app-layout>
