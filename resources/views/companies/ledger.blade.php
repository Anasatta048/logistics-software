<x-app-layout>
    <x-slot name="header">{{ __('Company Ledger') }}</x-slot>

    <div class="space-y-6">
        @if (session('success'))
            <x-alert variant="success">{{ session('success') }}</x-alert>
        @endif

        <div class="form-page-head !mb-0 flex-wrap">
            <span class="form-page-icon"><x-icon name="building" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ $company->name }}</h1>
                <p class="text-sm text-text-muted">
                    MC {{ $company->mc_number }} · {{ __('Account in USD') }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                @can('create', App\Models\CompanyPayment::class)
                    <x-button variant="primary" as="a" href="{{ route('company-payments.create', ['company_id' => $company->id]) }}">
                        <x-icon name="plus" :size="16" /> {{ __('Record payment') }}
                    </x-button>
                @endcan
                <a href="{{ route('companies.index') }}" class="text-sm text-text-muted hover:underline">{{ __('Back to Companies') }}</a>
            </div>
        </div>

        @include('ledger.summary', ['kind' => 'company', 'position' => $position])

        @include('ledger.open-items', ['kind' => 'company', 'position' => $position])

        <x-card :padded="false">
            <div class="border-b border-border/60 px-4 py-3 dark:border-white/10">
                <h3 class="text-sm font-semibold text-text-primary dark:text-white">{{ __('Recent payments') }}</h3>
            </div>
            @if ($payments->isEmpty())
                <p class="px-4 py-6 text-sm text-text-muted">{{ __('No payment has been recorded for this company yet.') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr>
                                <th class="px-4 py-3">{{ __('Date') }}</th>
                                <th class="px-4 py-3">{{ __('Reference') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Owed then') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Received') }}</th>
                                <th class="px-4 py-3">{{ __('Outcome') }}</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($payments as $payment)
                                @php
                                    $advance = round(max(0, (float) $payment->amount_received - (float) $payment->total_due), 2);
                                    $short = $payment->shortfall();
                                @endphp
                                <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $payment->payment_date->format('M j, Y') }}</td>
                                    <td class="px-4 py-3 text-text-muted">{{ $payment->reference ?: '—' }}</td>
                                    <td class="px-4 py-3 text-right">${{ number_format((float) $payment->total_due, 2) }}</td>
                                    <td class="px-4 py-3 text-right font-medium">${{ number_format((float) $payment->amount_received, 2) }}</td>
                                    <td class="px-4 py-3">
                                        @if ($short > 0)
                                            <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-500/20 dark:text-amber-300">
                                                {{ __('Partial · $:amount short', ['amount' => number_format($short, 2)]) }}
                                            </span>
                                        @elseif ($advance > 0)
                                            <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800 dark:bg-blue-500/20 dark:text-blue-300">
                                                {{ __('Advance · $:amount extra', ['amount' => number_format($advance, 2)]) }}
                                            </span>
                                        @else
                                            <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-500/20 dark:text-green-300">{{ __('Settled in full') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('company-payments.show', $payment) }}" class="text-brand-primary hover:underline">{{ __('Open') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        @include('ledger.statement', ['kind' => 'company', 'statement' => $statement, 'action' => route('companies.ledger', $company)])
    </div>
</x-app-layout>
