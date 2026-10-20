<x-app-layout>
    <x-slot name="header">{{ __('Company Payments') }}</x-slot>

    <div class="space-y-6">
        <div class="form-page-head !mb-0 flex-wrap">
            <span class="form-page-icon"><x-icon name="credit-card" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Company Payments') }}</h1>
                <p class="text-sm text-text-muted">
                    {{ __('Weekly USD receipts from companies. Recording one settles the commission on the loads it covers.') }}
                </p>
            </div>
            @can('create', App\Models\CompanyPayment::class)
                <x-button variant="primary" as="a" href="{{ route('company-payments.create') }}">
                    <x-icon name="plus" :size="16" /> {{ __('Record Payment') }}
                </x-button>
            @endcan
        </div>

        @if (session('success'))
            <x-alert variant="success">{{ session('success') }}</x-alert>
        @endif

        <x-card :padded="false">
            @if ($payments->isEmpty())
                <x-empty-state icon="receipt" title="No payments recorded"
                    description="Settle a week of company commission to create the first payment.">
                    <x-slot name="action">
                        @can('create', App\Models\CompanyPayment::class)
                            <x-button variant="primary" as="a" href="{{ route('company-payments.create') }}">{{ __('Record Payment') }}</x-button>
                        @endcan
                    </x-slot>
                </x-empty-state>
            @else
                <div class="overflow-x-auto">
                    <table data-table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr>
                                <th class="px-4 py-3">#</th>
                                <th data-filter="date" class="px-4 py-3">Date</th>
                                <th data-filter="select" class="px-4 py-3">Company</th>
                                <th class="px-4 py-3">Period</th>
                                <th class="px-4 py-3 text-right">Due</th>
                                <th class="px-4 py-3 text-right">Received</th>
                                <th class="px-4 py-3 text-right">Shortfall</th>
                                <th data-filter="select" class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Converted</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($payments as $payment)
                                <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 font-medium text-text-primary dark:text-white">
                                        <a href="{{ route('company-payments.show', $payment) }}" class="hover:underline">#{{ $payment->id }}</a>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap" data-date="{{ $payment->payment_date->toDateString() }}">{{ $payment->payment_date->format('M j, Y') }}</td>
                                    <td class="px-4 py-3">{{ $payment->company?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-xs text-text-muted whitespace-nowrap">
                                        {{ $payment->period_from->format('M j') }} – {{ $payment->period_to->format('M j, Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-right">${{ number_format((float) $payment->total_due, 2) }}</td>
                                    <td class="px-4 py-3 text-right">${{ number_format((float) $payment->amount_received, 2) }}</td>
                                    <td class="px-4 py-3 text-right {{ $payment->shortfall() > 0 ? 'text-status-cancelled' : 'text-text-muted' }}">
                                        ${{ number_format($payment->shortfall(), 2) }}
                                    </td>
                                    <td class="px-4 py-3"><x-status-badge :status="$payment->status" /></td>
                                    <td class="px-4 py-3 text-xs">
                                        @if ($payment->isLocked())
                                            <span class="text-status-paid">{{ $payment->conversion_date->format('M j, Y') }}</span>
                                        @else
                                            <span class="text-text-muted">{{ __('Not yet') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('company-payments.show', $payment) }}" class="text-text-muted hover:text-brand-primary" title="{{ __('View') }}">
                                                <x-icon name="eye" :size="16" />
                                            </a>
                                            @unless ($payment->isLocked())
                                                @can('update', $payment)
                                                    <a href="{{ route('company-payments.edit', $payment) }}" class="text-text-muted hover:text-brand-primary" title="{{ __('Edit') }}">
                                                        <x-icon name="pencil" :size="16" />
                                                    </a>
                                                @endcan
                                                @can('convert', $payment)
                                                    <a href="{{ route('company-payments.show', $payment) }}#convert" class="text-text-muted hover:text-brand-primary" title="{{ __('Convert to PKR') }}">
                                                        <x-icon name="wallet" :size="16" />
                                                    </a>
                                                @endcan
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        <div>{{ $payments->links() }}</div>
    </div>
</x-app-layout>
