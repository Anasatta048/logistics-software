<x-app-layout>
    <x-slot name="header">{{ __('Payment') }} #{{ $payment->id }}</x-slot>

    <div class="max-w-4xl space-y-6">
        <div class="form-page-head !mb-0 flex-wrap">
            <span class="form-page-icon"><x-icon name="credit-card" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">
                    {{ $payment->company?->name ?? __('Company') }}
                </h1>
                <p class="text-sm text-text-muted">
                    {{ $payment->payment_date->format(df()) }} &middot;
                    {{ $payment->period_from->format('M j') }} – {{ $payment->period_to->format(df()) }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <x-status-badge :status="$payment->status" />
                <a href="{{ route('company-payments.index') }}" class="text-sm text-text-muted hover:underline">
                    {{ __('Back to Payments') }}
                </a>
            </div>
        </div>

        @if ($payment->isLocked())
            <x-alert variant="info" inline>
                {{ __('This payment has been converted to PKR, so it is locked. Fix a mistake with a journal voucher or a new entry.') }}
            </x-alert>
        @else
            <div class="flex flex-wrap items-center gap-3">
                @can('update', $payment)
                    <x-button variant="secondary" as="a" href="{{ route('company-payments.edit', $payment) }}">
                        <x-icon name="pencil" :size="16" /> {{ __('Edit') }}
                    </x-button>
                @endcan
                @can('convert', $payment)
                    <x-button variant="primary" as="a" href="#convert">
                        <x-icon name="wallet" :size="16" /> {{ __('Convert to PKR') }}
                    </x-button>
                @endcan
            </div>
        @endif

        <x-card>
            <h3 class="text-sm font-semibold text-text-primary dark:text-white">{{ __('Amounts') }}</h3>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex items-center justify-between">
                    <dt class="text-text-muted">{{ __('Owed when this arrived (USD)') }}</dt>
                    <dd class="font-semibold text-text-primary dark:text-white">${{ number_format((float) $payment->total_due, 2) }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-text-muted">{{ __('Amount Received (USD)') }}</dt>
                    <dd class="font-semibold text-text-primary dark:text-white">${{ number_format((float) $payment->amount_received, 2) }}</dd>
                </div>
                @php $advance = round(max(0, (float) $payment->amount_received - (float) $payment->total_due), 2); @endphp
                <div class="flex items-center justify-between">
                    <dt class="text-text-muted">{{ __('Outcome') }}</dt>
                    <dd>
                        @if ($payment->shortfall() > 0)
                            <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-500/20 dark:text-amber-300">
                                {{ __('Partial payment — $:amount still owed', ['amount' => number_format($payment->shortfall(), 2)]) }}
                            </span>
                        @elseif ($advance > 0)
                            <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800 dark:bg-blue-500/20 dark:text-blue-300">
                                {{ __('$:amount held as advance', ['amount' => number_format($advance, 2)]) }}
                            </span>
                        @else
                            <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-500/20 dark:text-green-300">{{ __('Settled in full') }}</span>
                        @endif
                    </dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-text-muted">{{ __('Company ledger') }}</dt>
                    <dd><a href="{{ route('companies.ledger', $payment->company_id) }}" class="text-brand-primary hover:underline">{{ __('View statement') }}</a></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-text-muted">{{ __('Reference') }}</dt>
                    <dd>{{ $payment->reference ?: '—' }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-text-muted">{{ __('Recorded By') }}</dt>
                    <dd>{{ $payment->creator?->name ?? '—' }}</dd>
                </div>
                @if ($payment->attachment_path)
                    <div class="flex items-center justify-between">
                        <dt class="text-text-muted">{{ __('Attachment') }}</dt>
                        <dd>
                            <a href="{{ \Illuminate\Support\Facades\Storage::url($payment->attachment_path) }}"
                                class="text-brand-primary hover:underline" target="_blank">{{ __('Download') }}</a>
                        </dd>
                    </div>
                @endif
            </dl>
        </x-card>

        <x-card :padded="false">
            <div class="border-b border-border/60 px-4 py-3 dark:border-white/10">
                <h3 class="text-sm font-semibold text-text-primary dark:text-white">
                    {{ __('Loads settled by this payment') }} ({{ $loads->count() }})
                </h3>
            </div>
            @if ($loads->isEmpty())
                <p class="px-4 py-6 text-sm text-text-muted">{{ __('No loads attached.') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr>
                                <th class="px-4 py-3">Reference</th>
                                <th class="px-4 py-3">Pickup</th>
                                <th class="px-4 py-3 text-right">Rate</th>
                                <th class="px-4 py-3 text-right">Commission %</th>
                                <th class="px-4 py-3 text-right">Commission</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($loads as $load)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-text-primary dark:text-white">
                                        {{ $load->reference_number }}
                                    </td>
                                    <td class="px-4 py-3">{{ \App\Support\Timezones::toUs($load->pickup_at)?->format(df()) }}</td>
                                    <td class="px-4 py-3 text-right">${{ number_format((float) $load->rate, 2) }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format((float) $load->company_commission_percentage, 2) }}%</td>
                                    <td class="px-4 py-3 text-right font-semibold">
                                        ${{ number_format((float) $load->company_commission_amount, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        @if ($payment->isConverted())
            <x-card>
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-sm font-semibold text-text-primary dark:text-white">{{ __('Converted to PKR') }}</h3>
                    <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-500/20 dark:text-green-300">{{ __('Done') }}</span>
                </div>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-text-muted">{{ __('USD converted') }}</dt>
                        <dd class="font-medium">${{ number_format((float) $payment->converted_usd, 2) }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-text-muted">{{ __('Rate') }}</dt>
                        <dd>{{ rtrim(rtrim((string) $payment->conversion_rate, '0'), '.') }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-text-muted">{{ __('Gross PKR') }}</dt>
                        <dd>Rs {{ number_format((float) $payment->pkr_gross, 2) }}</dd>
                    </div>
                    @if ($payment->tax_applicable)
                        <div class="flex items-center justify-between">
                            <dt class="text-text-muted">{{ __('Tax') }} ({{ $payment->tax_type?->label() }} {{ $payment->tax_value }})</dt>
                            <dd class="text-status-cancelled">− Rs {{ number_format((float) $payment->tax_amount, 2) }}</dd>
                        </div>
                    @endif
                    <div class="flex items-center justify-between border-t border-border/60 pt-3 dark:border-white/10">
                        <dt class="font-medium">{{ __('Net deposited') }}</dt>
                        <dd class="text-base font-semibold text-status-paid">Rs {{ number_format((float) $payment->pkr_net, 2) }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-text-muted">{{ __('Payment method') }}</dt>
                        <dd>{{ $payment->payment_method?->label() ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-text-muted">{{ __('Into account') }}</dt>
                        <dd>{{ $payment->depositAccount?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-text-muted">{{ __('Converted on') }}</dt>
                        <dd>{{ $payment->conversion_date?->format(df()) }} · {{ $payment->converter?->name ?? '—' }}</dd>
                    </div>
                    @if ($payment->conversion_notes)
                        <div class="text-text-muted">{{ $payment->conversion_notes }}</div>
                    @endif
                </dl>
            </x-card>
        @else
            @can('convert', $payment)
                <x-card id="convert">
                    <h3 class="text-sm font-semibold text-text-primary dark:text-white">{{ __('Convert to PKR') }}</h3>
                    <p class="mt-1 text-xs text-text-muted">
                        {{ __('Sell the dollars and deposit the PKR into an account. After this the payment is locked — a mistake is fixed with a journal voucher.') }}
                    </p>

                    <form method="POST" action="{{ route('company-payments.convert', $payment) }}" class="mt-4">
                        @csrf
                        @include('company-payments.partials.conversion-fields', [
                            'accounts' => $accounts, 'rate' => $rate, 'askAmount' => true, 'maxUsd' => (float) $payment->amount_received,
                        ])
                        <div class="mt-4 flex items-center gap-3">
                            <x-button variant="primary" type="submit"><x-icon name="check-circle" :size="16" /> {{ __('Convert and deposit') }}</x-button>
                        </div>
                    </form>
                </x-card>
            @endcan
        @endif
    </div>
</x-app-layout>
