<x-app-layout>
    <x-slot name="header">{{ __('Invoice') }} #{{ $invoice->invoice_number }}</x-slot>

    <div class="space-y-5">
        @if (session('success'))
            <x-alert variant="success">{{ session('success') }}</x-alert>
        @endif
        @if ($errors->has('invoice'))
            <x-alert variant="danger">{{ $errors->first('invoice') }}</x-alert>
        @endif

        @php
            $due = $invoice->due();
            $paidAmt = $invoice->amountPaid();
            $paidPct = $due > 0 ? min(100, round($paidAmt / $due * 100)) : 0;
            $overdue = $invoice->balance() > 0 && $invoice->due_date->isPast() && ! $invoice->isCarried();
        @endphp

        {{-- Hero --}}
        <section class="relative overflow-hidden bg-brand-navy text-white">
            <span class="absolute inset-x-0 bottom-0 h-1 bg-brand-primary"></span>
            <div class="relative flex flex-wrap items-start justify-between gap-6 p-6 sm:p-8">
                <div class="min-w-0">
                    <a href="{{ route('invoices.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-white/60 hover:text-white"><x-icon name="arrow-left" :size="13" /> {{ __('All invoices') }}</a>
                    <p class="mt-3 text-[11px] font-bold uppercase tracking-[0.25em] text-brand-primary">{{ __('Invoice') }}</p>
                    <h1 class="mt-1 flex flex-wrap items-center gap-3 text-3xl font-extrabold tracking-tight">
                        #{{ $invoice->invoice_number }}
                        @if ($invoice->isCarried())
                            <span class="lookup-badge">{{ __('Carried to #:n', ['n' => $invoice->carriedTo?->invoice_number]) }}</span>
                        @else
                            <x-status-badge :status="$invoice->status()" />
                        @endif
                        @if ($overdue)
                            <span class="bg-status-cancelled px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide">{{ __('Overdue') }}</span>
                        @endif
                    </h1>
                    <p class="mt-2 text-sm text-white/70">
                        <x-icon name="building" :size="14" class="mr-1 inline" />{{ $invoice->company->name }}
                        <span class="mx-2 text-white/30">|</span>
                        <x-icon name="user" :size="14" class="mr-1 inline" />{{ $invoice->driver_name }}
                        <span class="mx-2 text-white/30">|</span>
                        <x-icon name="calendar" :size="14" class="mr-1 inline" />{{ $invoice->period_from->format('M j') }} – {{ $invoice->period_to->format('M j, Y') }}
                    </p>
                </div>

                <div class="text-right">
                    <p class="text-xs uppercase tracking-wider text-white/60">{{ $invoice->balance() > 0 ? __('Balance due') : __('Total due') }}</p>
                    <p class="text-4xl font-extrabold">${{ number_format($invoice->balance() > 0 ? $invoice->balance() : $due, 2) }}</p>
                    <p class="mt-1 text-xs text-white/60">{{ __('Due') }} {{ $invoice->due_date->format('M j, Y') }}</p>
                </div>
            </div>

            <div class="relative flex flex-wrap items-center gap-2 border-t border-white/10 px-6 py-4 sm:px-8">
                <a href="{{ route('invoices.pdf', [$invoice, 'inline' => 1]) }}" target="_blank" rel="noopener" data-no-loader class="btn-ghost btn-sm !border-white/20 !text-white hover:!border-brand-primary"><x-icon name="eye" :size="14" /> {{ __('Open PDF') }}</a>
                <a href="{{ route('invoices.pdf', $invoice) }}" data-no-loader class="btn-primary btn-sm"><x-icon name="download" :size="14" /> {{ __('Download PDF') }}</a>
                @if ($invoice->balance() > 0 && ! $invoice->isCarried())
                    @can('create', App\Models\CompanyPayment::class)
                        <button type="button" class="btn-ghost btn-sm !border-white/20 !text-white hover:!border-brand-primary" x-data x-on:click="$dispatch('open-modal', 'record-payment')">
                            <x-icon name="wallet" :size="14" /> {{ __('Record payment') }}
                        </button>
                    @endcan
                @endif
                @can('delete', $invoice)
                    <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" class="ml-auto">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-ghost btn-sm !border-white/20 !text-red-300 hover:!border-red-400" data-confirm="Delete this invoice? Its loads can be invoiced again.">
                            <x-icon name="trash" :size="14" /> {{ __('Delete') }}
                        </button>
                    </form>
                @endcan
            </div>
        </section>

        {{-- Money at a glance --}}
        <div class="grid grid-cols-2 gap-px border border-border bg-border lg:grid-cols-4 dark:border-white/10 dark:bg-white/10">
            @foreach ([
                [__('Total due'), '$'.number_format($due, 2), 'dollar', 'text-brand-primary'],
                [__('Received'), '$'.number_format($paidAmt, 2), 'check-circle', 'text-status-paid'],
                [__('Invoice date'), $invoice->invoice_date->format('M j, Y'), 'calendar', 'text-text-primary dark:text-white'],
                [__('Due date'), $invoice->due_date->format('M j, Y'), 'clock', $overdue ? 'text-status-cancelled' : 'text-text-primary dark:text-white'],
            ] as [$label, $value, $icon, $tone])
                <div class="flex items-center gap-3 bg-surface p-4 dark:bg-surface-dark">
                    <span class="grid h-10 w-10 shrink-0 place-items-center bg-brand-primary/10 text-brand-primary"><x-icon :name="$icon" :size="18" /></span>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-text-muted">{{ $label }}</p>
                        <p class="text-lg font-extrabold {{ $tone }}">{{ $value }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="border border-border bg-surface p-4 dark:border-white/10 dark:bg-surface-dark">
            <div class="mb-2 flex items-center justify-between text-xs">
                <span class="font-bold uppercase tracking-wider text-text-muted">{{ __('Payment progress') }}</span>
                <span class="font-bold text-text-primary dark:text-white">{{ $paidPct }}% {{ __('received') }}</span>
            </div>
            <div class="h-2 bg-border dark:bg-white/10"><div class="h-full bg-status-paid transition-all duration-700" style="width: {{ $paidPct }}%"></div></div>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <x-card :padded="false">
                    <div class="border-b border-border/60 px-5 py-3 text-sm font-semibold text-text-primary dark:border-white/10 dark:text-white">
                        {{ __('Loads on this invoice') }}
                        <span class="ml-1 text-xs font-normal text-text-muted">
                            {{ $invoice->period_from->format('M j') }} – {{ $invoice->period_to->format('M j, Y') }}
                        </span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                                <tr>
                                    <th class="px-4 py-3">{{ __('Date') }}</th>
                                    <th class="px-4 py-3">{{ __('Route') }}</th>
                                    <th class="px-4 py-3 text-right">RPM</th>
                                    <th class="px-4 py-3 text-right">%</th>
                                    <th class="px-4 py-3 text-right">{{ __('Load rate') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Fee') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border/60 dark:divide-white/5">
                                @foreach ($invoice->lines as $line)
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap">{{ $line->load_date->format('M j, Y') }}</td>
                                        <td class="px-4 py-3">
                                            {{ $line->description }}
                                            <span class="block text-xs text-text-muted">{{ $line->loadRecord?->reference_number }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right text-text-muted">{{ $line->ratePerMile() !== null ? '$'.number_format($line->ratePerMile(), 2) : '—' }}</td>
                                        <td class="px-4 py-3 text-right text-text-muted">{{ rtrim(rtrim(number_format((float) $line->percentage, 2), '0'), '.') }}%</td>
                                        <td class="px-4 py-3 text-right text-text-muted">${{ number_format((float) $line->rate, 2) }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-text-primary dark:text-white">${{ number_format((float) $line->fee, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t border-border dark:border-white/10 text-text-primary dark:text-white">
                                <tr>
                                    <td colspan="4" class="px-4 py-2 text-right text-text-muted">{{ __('Commission on loads') }}</td>
                                    <td class="px-4 py-2 text-right text-text-muted">${{ number_format((float) $invoice->gross_total, 2) }}</td>
                                    <td class="px-4 py-2 text-right font-semibold">${{ number_format((float) $invoice->total, 2) }}</td>
                                </tr>
                                @if ((float) $invoice->previous_balance > 0)
                                    <tr>
                                        <td colspan="5" class="px-4 py-2 text-right text-text-muted">{{ __('Previous pending added') }}</td>
                                        <td class="px-4 py-2 text-right">+ ${{ number_format((float) $invoice->previous_balance, 2) }}</td>
                                    </tr>
                                @endif
                                @if ((float) $invoice->credit_applied > 0)
                                    <tr>
                                        <td colspan="5" class="px-4 py-2 text-right text-text-muted">{{ __('Advance credit deducted') }}</td>
                                        <td class="px-4 py-2 text-right">− ${{ number_format((float) $invoice->credit_applied, 2) }}</td>
                                    </tr>
                                @endif
                                <tr class="font-bold">
                                    <td colspan="5" class="px-4 py-3 text-right">{{ __('Total due') }}</td>
                                    <td class="px-4 py-3 text-right text-brand-primary">${{ number_format($invoice->due(), 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </x-card>

                <x-card :padded="false">
                    <div class="border-b border-border/60 px-5 py-3 text-sm font-semibold text-text-primary dark:border-white/10 dark:text-white">
                        {{ __('Payments received') }}
                    </div>
                    @if ($invoice->payments->isEmpty())
                        <p class="px-5 py-6 text-sm text-text-muted">{{ __('Nothing received yet — this invoice is Pending.') }}</p>
                    @else
                        <ul class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($invoice->payments as $payment)
                                <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 text-sm">
                                    <div>
                                        <a href="{{ route('company-payments.show', $payment) }}" class="font-medium text-text-primary hover:text-brand-primary dark:text-white">
                                            ${{ number_format((float) $payment->amount_received, 2) }}
                                        </a>
                                        <span class="ml-2 text-xs text-text-muted">
                                            {{ $payment->payment_date->format('M j, Y') }} · {{ $payment->payment_method?->label() ?? '—' }}
                                            @if ($payment->reference) · {{ $payment->reference }} @endif
                                        </span>
                                    </div>
                                    <a href="{{ route('company-payments.show', $payment) }}" class="text-xs font-semibold text-brand-primary hover:underline">
                                        {{ $payment->isConverted() ? __('Converted to PKR') : __('Convert to PKR') }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>

                <x-card class="space-y-3">
                    <h2 class="text-sm font-semibold text-text-primary dark:text-white">{{ __('PDF preview') }}</h2>
                    <iframe src="{{ route('invoices.pdf', [$invoice, 'inline' => 1]) }}#toolbar=0" title="Invoice PDF"
                        class="h-[900px] w-full rounded-card border border-border/70 bg-white dark:border-white/10"></iframe>
                </x-card>
            </div>

            <div class="space-y-5">
                <x-card class="space-y-3 text-sm">
                    <h2 class="text-sm font-semibold text-text-primary dark:text-white">{{ __('Bill to') }}</h2>
                    <div>
                        <div class="font-medium text-text-primary dark:text-white">{{ $invoice->company->name }}</div>
                        <div class="text-text-muted">MC # {{ $invoice->company->mc_number }}</div>
                        @if ($invoice->company->contact_person)<div class="text-text-muted">{{ $invoice->company->contact_person }}</div>@endif
                        @if ($invoice->company->address)<div class="text-text-muted">{{ $invoice->company->address }}</div>@endif
                        @if ($invoice->company->phone)<div class="text-text-muted">{{ $invoice->company->phone }}</div>@endif
                        @if ($invoice->company->email)<div class="text-text-muted">{{ $invoice->company->email }}</div>@endif
                    </div>
                    <dl class="space-y-1 border-t border-border/60 pt-3 dark:border-white/10">
                        <div class="flex justify-between"><dt class="text-text-muted">{{ __('Driver') }}</dt><dd>{{ $invoice->driver_name }}</dd></div>
                        @if ($invoice->dispatcher_name)
                            <div class="flex justify-between"><dt class="text-text-muted">{{ __('Dispatcher') }}</dt><dd>{{ $invoice->dispatcher_name }}</dd></div>
                        @endif
                        <div class="flex justify-between"><dt class="text-text-muted">{{ __('Created by') }}</dt><dd>{{ $invoice->creator?->name ?? '—' }}</dd></div>
                    </dl>
                    @if ($invoice->notes)
                        <p class="border-t border-border/60 pt-3 text-text-muted dark:border-white/10">{{ $invoice->notes }}</p>
                    @endif
                </x-card>
            </div>
        </div>
    </div>
    @if ($invoice->balance() > 0)
        @can('create', App\Models\CompanyPayment::class)
            <x-modal name="record-payment" maxWidth="md" :show="$errors->has('amount_received') || $errors->has('payment_method') || $errors->has('payment_date')">
                <form method="POST" action="{{ route('invoices.pay', $invoice) }}" class="space-y-4 p-6">
                    @csrf
                    <div>
                        <h4 class="text-base font-semibold text-text-primary dark:text-white">{{ __('Record payment') }}</h4>
                        <p class="text-sm text-text-muted">
                            {{ __('Invoice #:n — balance :b. Every payment is posted to the company\'s ledger.', ['n' => $invoice->invoice_number, 'b' => '$'.number_format($invoice->balance(), 2)]) }}
                        </p>
                    </div>
                    <div x-data="{
                            balance: {{ number_format($invoice->balance(), 2, '.', '') }},
                            amount: {{ json_encode(old('amount_received', number_format($invoice->balance(), 2, '.', ''))) }},
                            mode: 'full',
                            money(v) { return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(v || 0); },
                            pick(m) { this.mode = m; if (m === 'full') this.amount = this.balance.toFixed(2); else { this.$nextTick(() => this.$refs.amt.focus()); } },
                            get diff() { return Math.round((parseFloat(this.amount || 0) - this.balance) * 100) / 100; },
                        }"
                        class="space-y-4">
                        <div>
                            <p class="mb-2 text-sm font-medium text-text-primary dark:text-white">{{ __('How much did they pay?') }}</p>
                            <div class="grid grid-cols-3 gap-2 text-center text-sm">
                                <button type="button" x-on:click="pick('full')" :class="mode === 'full' ? 'border-brand-primary bg-brand-primary/10 text-brand-primary' : 'border-border text-text-muted'"
                                    class="rounded-card border px-2 py-2 font-medium transition">{{ __('Full amount') }}</button>
                                <button type="button" x-on:click="pick('less')" :class="mode === 'less' ? 'border-brand-primary bg-brand-primary/10 text-brand-primary' : 'border-border text-text-muted'"
                                    class="rounded-card border px-2 py-2 font-medium transition">{{ __('Less') }}</button>
                                <button type="button" x-on:click="pick('more')" :class="mode === 'more' ? 'border-brand-primary bg-brand-primary/10 text-brand-primary' : 'border-border text-text-muted'"
                                    class="rounded-card border px-2 py-2 font-medium transition">{{ __('More') }}</button>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="amount_received" :value="__('Amount received (USD)')" />
                                <input id="amount_received" name="amount_received" type="number" step="0.01" min="0.01" required
                                    x-ref="amt" x-model="amount" x-on:input="mode = diff === 0 ? 'full' : (diff < 0 ? 'less' : 'more')"
                                    class="mt-1 block w-full rounded-input border-border bg-surface text-text-primary shadow-sm focus:border-brand-primary focus:ring-brand-primary dark:bg-surface-dark dark:border-white/10 dark:text-white">
                                <x-input-error class="mt-2" :messages="$errors->get('amount_received')" />
                            </div>
                            <div>
                                <x-input-label for="payment_date" :value="__('Payment date')" />
                                <x-text-input id="payment_date" name="payment_date" type="date" class="mt-1 block w-full"
                                    :value="old('payment_date', $today)" required />
                                <x-input-error class="mt-2" :messages="$errors->get('payment_date')" />
                            </div>
                            <x-payment-method-field />
                            <div>
                                <x-input-label for="reference" :value="__('Reference (optional)')" />
                                <x-text-input id="reference" name="reference" type="text" class="mt-1 block w-full" :value="old('reference')" />
                            </div>
                        </div>

                        <p class="rounded-card px-3 py-2 text-sm"
                            :class="diff === 0 ? 'bg-status-paid/10 text-status-paid' : (diff < 0 ? 'bg-status-dispatched/10 text-status-dispatched' : 'bg-brand-primary/10 text-brand-primary')">
                            <template x-if="diff === 0"><span>{{ __('Settles the invoice in full.') }}</span></template>
                            <template x-if="diff < 0"><span x-text="money(-diff) + ' will still be owed — the invoice stays partially settled.'"></span></template>
                            <template x-if="diff > 0"><span x-text="money(diff) + ' extra is kept as the company\'s advance credit, which you can deduct from its next invoice.'"></span></template>
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <x-button type="button" variant="ghost" x-on:click="$dispatch('close-modal', 'record-payment')">{{ __('Cancel') }}</x-button>
                        <x-button type="submit" variant="primary">{{ __('Save payment') }}</x-button>
                    </div>
                </form>
            </x-modal>
        @endcan
    @endif
</x-app-layout>
