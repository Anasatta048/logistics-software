@php
    /**
     * @var \App\Models\CompanyPayment|null $payment  null when recording a new one
     * @var \Illuminate\Support\Collection $accounts  active accounts (for the optional PKR conversion)
     */
    $editing = $payment !== null;
    $selectedCompanyId = (int) old('company_id', $payment?->company_id ?? $company?->id ?? 0);
    $field = 'mt-1 block w-full rounded-input border-border bg-surface text-text-primary shadow-sm focus:border-brand-primary focus:ring-brand-primary dark:bg-surface-dark dark:border-white/10 dark:text-white';
    $heading = 'text-sm font-semibold text-text-primary dark:text-white';
@endphp

<div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3"
    x-data="paymentForm(@js([
        'suggestionsUrl' => route('company-payments.suggestions'),
        'balanceUrl' => route('company-payments.balance'),
        'editing' => $editing,
        'convert' => ! $editing && (bool) old('convert', false),
    ]))">

    {{-- ───────── Left: what was received ───────── --}}
    <div class="form-anim space-y-5 lg:col-span-2">
        <x-form-section :title="__('Who paid, and for when')" icon="building" :description="__('Pick the company and the period this payment covers.')" :step="1">
            <x-field name="company_id" class="span-2" :label="__('Company')" icon="building" required>
                <select id="company_id" name="company_id" required x-model="companyId" x-on:change="loadLoads()" @disabled($editing)>
                    <option value="">{{ __('Select a company') }}</option>
                    @foreach ($companies as $companyOption)
                        <option value="{{ $companyOption->id }}" @selected($selectedCompanyId === $companyOption->id)>{{ $companyOption->name }} ({{ $companyOption->mc_number }})</option>
                    @endforeach
                </select>
                @if ($editing)
                    <input type="hidden" name="company_id" value="{{ $payment->company_id }}">
                @endif
            </x-field>
            @if ($editing)
                <p class="field-hint span-2 -mt-3">{{ __('The company cannot be changed on a recorded payment.') }}</p>
            @endif
            <x-field name="payment_date" :label="__('Payment date')" icon="calendar" required>
                <input id="payment_date" name="payment_date" type="date" value="{{ old('payment_date', $payment?->payment_date?->toDateString() ?? \App\Support\Timezones::today()) }}" required>
            </x-field>
            <x-field name="payment_method" :label="__('Payment method')" icon="credit-card" required>
                <select id="payment_method" name="payment_method" required>
                    <option value="">{{ __('Select method') }}</option>
                    @foreach (\App\Enums\PaymentMethod::enabled(old('payment_method')) as $method)
                        <option value="{{ $method->value }}" @selected(old('payment_method', $payment?->payment_method?->value) === $method->value)>{{ $method->label() }}</option>
                    @endforeach
                </select>
            </x-field>
            <x-field name="period_from" :label="__('Covers from')" icon="calendar" required>
                <input id="period_from" name="period_from" type="date" value="{{ old('period_from', $payment?->period_from?->toDateString() ?? now()->startOfWeek()->toDateString()) }}" required>
            </x-field>
            <x-field name="period_to" :label="__('Covers up to')" icon="calendar" required>
                <input id="period_to" name="period_to" type="date" value="{{ old('period_to', $payment?->period_to?->toDateString() ?? \App\Support\Timezones::today()) }}" required>
            </x-field>
        </x-form-section>

        <x-form-section :title="__('Money received')" icon="dollar" :description="__('Any amount works: less than owed is a partial payment, more is kept as an advance.')" :step="2">
            <x-field name="amount_received" :label="__('Amount received (USD)')" icon="dollar" required>
                <input id="amount_received" name="amount_received" type="number" step="0.01" min="0" value="{{ old('amount_received', $payment?->amount_received) }}" x-model="received" x-on:input="touched = true" required placeholder="0.00">
            </x-field>
            <x-field name="reference" :label="__('Reference')" icon="hash">
                <input id="reference" name="reference" type="text" value="{{ old('reference', $payment?->reference) }}" placeholder="{{ __('Bank ref, Zelle confirmation…') }}">
            </x-field>
            <x-field name="attachment" class="span-2" :label="__('Proof (optional)')" icon="upload" :hint="__('PDF, JPG or PNG — compressed automatically.')">
                <input id="attachment" name="attachment" type="file" accept="application/pdf,image/*">
            </x-field>
            @if ($payment?->attachment_path)
                <p class="field-hint span-2 -mt-3">
                    {{ __('Current file:') }}
                    <a href="{{ \Illuminate\Support\Facades\Storage::url($payment->attachment_path) }}" class="font-semibold text-brand-primary hover:underline" target="_blank">{{ __('Download') }}</a>
                </p>
            @endif
        </x-form-section>

        {{-- Optional: convert to PKR in the same save --}}
        @unless ($editing)
            <section class="form-section">
                <label class="form-section-head cursor-pointer">
                    <input type="hidden" name="convert" value="0">
                    <input type="checkbox" name="convert" value="1" x-model="convert" class="h-5 w-5 text-brand-primary focus:ring-brand-primary">
                    <span class="min-w-0">
                        <span class="form-section-title"><span class="form-section-step">3</span>{{ __('Convert to PKR now (optional)') }}</span>
                        <span class="form-section-desc block">{{ __('Tick this if the dollars were already sold. Leave it off to convert later from the payment page.') }}</span>
                    </span>
                </label>
                <fieldset class="p-5" x-show="convert" x-cloak x-bind:disabled="! convert">
                    <div x-data="{ amount: 0 }" x-effect="amount = Number(received) || 0">
                        @include('company-payments.partials.conversion-fields', ['accounts' => $accounts, 'rate' => $rate, 'askAmount' => false])
                    </div>
                </fieldset>
            </section>
        @endunless
    </div>

    {{-- ───────── Right: live summary ───────── --}}
    <div class="space-y-4 lg:sticky lg:top-4">
        <section class="form-section">
            <header class="form-section-head">
                <span class="form-section-icon"><x-icon name="receipt" :size="18" /></span>
                <h3 class="form-section-title">{{ __('Summary') }}</h3>
            </header>
            <div class="p-5">
                @if ($editing)
                    <p class="text-xs text-text-muted">
                        {{ __('Saving reverses the original entry on the company ledger and posts the corrected one, so both stay visible. It is then matched to the open commissions oldest first.') }}
                    </p>
                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <dt class="text-text-muted">{{ __('Recorded as') }}</dt>
                            <dd class="font-semibold">${{ number_format((float) $payment->amount_received, 2) }}</dd>
                        </div>
                    </dl>
                @else
                    <p class="text-xs text-text-muted" x-show="! companyId">{{ __('Choose a company to see what it owes.') }}</p>
                    <p class="mt-2 text-xs text-status-cancelled" x-show="message" x-cloak x-text="message"></p>

                    <dl class="space-y-2 text-sm" x-show="companyId" x-cloak>
                        <div class="flex items-center justify-between">
                            <dt class="text-text-muted">{{ __('Company owes') }}</dt>
                            <dd class="font-semibold" x-text="money(balance.outstanding)"></dd>
                        </div>
                        <div class="flex items-center justify-between" x-show="balance.advance > 0" x-cloak>
                            <dt class="text-text-muted">{{ __('Advance already held') }}</dt>
                            <dd class="font-semibold text-blue-600 dark:text-blue-300" x-text="money(balance.advance)"></dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-text-muted">{{ __('This payment') }}</dt>
                            <dd class="font-semibold" x-text="money(amount)"></dd>
                        </div>
                        <div class="flex items-center justify-between border-t border-border pt-2 dark:border-white/10">
                            <dt class="text-text-muted">{{ __('Balance after') }}</dt>
                            <dd class="text-lg font-bold" :class="balanceAfter > 0 ? 'text-status-cancelled' : (balanceAfter < 0 ? 'text-blue-600 dark:text-blue-300' : 'text-status-paid')"
                                x-text="money(Math.abs(balanceAfter)) + (balanceAfter < 0 ? ' advance' : '')"></dd>
                        </div>
                    </dl>

                    <div class="mt-3 px-3 py-2 text-xs font-semibold" x-show="companyId && amount > 0" x-cloak :class="outcome.tone" x-text="outcome.text"></div>
                @endif

                <div class="mt-6 flex flex-col gap-3">
                    <button type="submit" class="btn-primary w-full">
                        <x-icon name="check-circle" :size="16" /> {{ $editing ? __('Save changes') : __('Record payment') }}
                    </button>
                    <a href="{{ $editing ? route('company-payments.show', $payment) : route('company-payments.index') }}" class="btn-ghost w-full">{{ __('Cancel') }}</a>
                </div>
            </div>
        </section>

        @unless ($editing)
            <section class="form-section">
                <header class="form-section-head">
                    <span class="form-section-icon"><x-icon name="list" :size="18" /></span>
                    <div>
                        <h3 class="form-section-title">{{ __('Where it will go') }}</h3>
                        <p class="form-section-desc">{{ __('Oldest commission first. Nothing to tick.') }}</p>
                    </div>
                </header>
                <div class="max-h-72 overflow-y-auto px-4 py-2">
                    <template x-for="row in allocation" :key="row.id">
                        <div class="flex items-center gap-2 border-b border-border py-2 text-sm last:border-0 dark:border-white/5">
                            <div class="min-w-0 flex-1">
                                <div class="font-medium text-text-primary dark:text-white" x-text="row.reference_number"></div>
                                <div class="text-xs text-text-muted" x-text="row.pickup_at"></div>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold" x-text="money(row.take) + ' / ' + money(row.outstanding)"></div>
                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase" :class="row.chip" x-text="row.label"></span>
                            </div>
                        </div>
                    </template>
                    <p class="py-4 text-center text-sm text-text-muted" x-show="! loaded">{{ __('Choose a company first.') }}</p>
                    <p class="py-4 text-center text-sm text-text-muted" x-show="loaded && loads.length === 0" x-cloak>{{ __('Nothing outstanding for this company.') }}</p>
                </div>
            </section>
        @endunless
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('paymentForm', (config) => ({
                companyId: @js($selectedCompanyId ?: ''),
                balance: { invoiced: 0, received: 0, advance: 0, outstanding: 0 },
                loads: [],
                loaded: false,
                message: '',
                received: @js((string) old('amount_received', $payment?->amount_received ?? '')),
                touched: {{ $editing || old('amount_received') !== null ? 'true' : 'false' }},
                convert: config.convert,

                init() {
                    if (! config.editing) {
                        this.loadLoads();
                    }
                },

                get amount() {
                    return Number(this.received) || 0;
                },

                get balanceAfter() {
                    // Owed minus what is already held in advance, minus this payment.
                    return Math.round((this.balance.outstanding - this.balance.advance - this.amount) * 100) / 100;
                },

                get outcome() {
                    const owed = this.balance.outstanding;
                    const a = this.amount;

                    if (a < owed - 0.004) {
                        return { tone: 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300', text: 'Partial payment — ' + this.money(owed - a) + ' will still be owed.' };
                    }

                    if (a > owed + 0.004) {
                        return { tone: 'bg-blue-100 text-blue-800 dark:bg-blue-500/20 dark:text-blue-300', text: this.money(a - owed) + ' is kept as an advance and used up by the next commission.' };
                    }

                    return { tone: 'bg-green-100 text-green-800 dark:bg-green-500/20 dark:text-green-300', text: 'Settles everything the company owes.' };
                },

                // The oldest open commission takes the money first.
                get allocation() {
                    let left = this.amount;

                    return this.loads.map((load) => {
                        const take = Math.max(0, Math.min(Number(load.outstanding), left));
                        left -= take;
                        const full = take >= Number(load.outstanding) - 0.004;

                        return {
                            ...load,
                            take,
                            label: take <= 0 ? 'Unpaid' : (full ? 'Paid in full' : 'Partly paid'),
                            chip: take <= 0
                                ? 'bg-red-100 text-red-800 dark:bg-red-500/20 dark:text-red-300'
                                : (full ? 'bg-green-100 text-green-800 dark:bg-green-500/20 dark:text-green-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300'),
                        };
                    });
                },

                money(value) {
                    return '$' + Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                async loadLoads() {
                    if (! this.companyId) {
                        this.balance = { invoiced: 0, received: 0, advance: 0, outstanding: 0 };
                        this.loads = [];
                        this.loaded = false;

                        return;
                    }

                    try {
                        const [balance, suggestions] = await Promise.all([
                            this.getJson(`${config.balanceUrl}?company_id=${this.companyId}`),
                            this.getJson(`${config.suggestionsUrl}?company_id=${this.companyId}`),
                        ]);

                        this.balance = balance;
                        this.loads = suggestions.loads;
                        this.loaded = true;
                        this.message = '';

                        // Default to what is actually owed; a partial payment is typed over it.
                        if (! this.touched) {
                            this.received = Number(balance.outstanding || 0).toFixed(2);
                        }
                    } catch (error) {
                        this.loads = [];
                        this.loaded = true;
                        this.message = 'Could not load the outstanding balance. Check the company, then retry.';
                    }
                },

                async getJson(url) {
                    const response = await fetch(url, { headers: { Accept: 'application/json' } });

                    if (! response.ok) {
                        throw new Error('request failed');
                    }

                    return response.json();
                },
            }));
        });
    </script>
@endpush
