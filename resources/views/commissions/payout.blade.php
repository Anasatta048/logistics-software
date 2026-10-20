@php
    $select = 'mt-1 block w-full rounded-input border-border bg-surface text-text-primary shadow-sm focus:border-brand-primary focus:ring-brand-primary dark:bg-surface-dark dark:border-white/10 dark:text-white';
    $owed = $position['outstanding'] ?? 0;
@endphp
<x-app-layout>
    <x-slot name="header">{{ __('Pay Dispatcher') }}</x-slot>

    <div class="max-w-3xl space-y-6">
        <x-card>
            <form method="GET" action="{{ route('commissions.payouts.create') }}">
                <x-input-label for="dispatcher_id" :value="__('Dispatcher')" />
                <select id="dispatcher_id" name="dispatcher_id" class="{{ $select }}" onchange="this.form.submit()">
                    <option value="">{{ __('Select a dispatcher') }}</option>
                    @foreach ($dispatchers as $option)
                        <option value="{{ $option->id }}" @selected($dispatcher?->id === $option->id)>{{ $option->name }}</option>
                    @endforeach
                </select>
            </form>
        </x-card>

        @if ($dispatcher)
            @include('ledger.summary', ['kind' => 'dispatcher', 'position' => $position])

            <form method="POST" action="{{ route('commissions.payouts.store') }}"
                x-data="{
                    usd: @js(old('usd_amount', $owed > 0 ? number_format($owed, 2, '.', '') : '')),
                    rate: @js(old('conversion_rate', $rate)),
                    accountId: @js(old('payment_account_id')),
                    balances: @js($balances),
                    owed: {{ $owed }},
                    pkr() { return (parseFloat(this.usd) || 0) * (parseFloat(this.rate) || 0) },
                    available() { return parseFloat(this.balances[this.accountId] ?? 0) },
                    short() { return this.pkr() > this.available() },
                    amount() { return parseFloat(this.usd) || 0 },
                    fmt(value) { return value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
                    outcome() {
                        const a = this.amount();
                        if (a <= 0) return '';
                        if (a < this.owed - 0.004) return 'Partial payment — $' + this.fmt(this.owed - a) + ' will still be owed';
                        if (a > this.owed + 0.004) return '$' + this.fmt(a - this.owed) + ' goes in as an advance, used up by the next commission';
                        return 'Settles the full balance';
                    }
                }">
                @csrf
                <input type="hidden" name="dispatcher_id" value="{{ $dispatcher->id }}">

                <x-card>
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-text-primary dark:text-white">{{ __('Payout details') }}</h3>
                        <div class="flex items-center gap-2 text-xs">
                            @if ($owed > 0)
                                <button type="button" class="rounded-full border border-border px-2.5 py-1 hover:border-brand-primary hover:text-brand-primary dark:border-white/10"
                                    x-on:click="usd = {{ number_format($owed, 2, '.', '') }}">{{ __('Full balance') }}</button>
                                <button type="button" class="rounded-full border border-border px-2.5 py-1 hover:border-brand-primary hover:text-brand-primary dark:border-white/10"
                                    x-on:click="usd = {{ number_format($owed / 2, 2, '.', '') }}">{{ __('Half') }}</button>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="usd_amount" :value="__('Amount to pay (USD)')" />
                            <x-text-input id="usd_amount" name="usd_amount" type="number" step="0.01" min="0.01"
                                class="mt-1 block w-full" x-model="usd" required />
                            <p class="mt-1 text-xs" :class="amount() > owed + 0.004 ? 'text-blue-600 dark:text-blue-300' : 'text-text-muted'"
                                x-text="outcome() || '{{ __('Any amount: a part payment, the full balance, or an advance.') }}'"></p>
                            <x-input-error class="mt-2" :messages="$errors->get('usd_amount')" />
                        </div>

                        <div>
                            <x-input-label for="conversion_rate" :value="__('USD → PKR rate')" />
                            <x-text-input id="conversion_rate" name="conversion_rate" type="number" step="0.0001" min="0.0001"
                                class="mt-1 block w-full" x-model="rate" required />
                            <p class="mt-1 text-xs text-text-muted">
                                {{ $rate ? __('Pre-filled from the last rate used.') : __('Type today\'s rate.') }}
                            </p>
                            <x-input-error class="mt-2" :messages="$errors->get('conversion_rate')" />
                        </div>

                        <x-payment-method-field />

                        <div>
                            <x-input-label for="payment_account_id" :value="__('Paid from (optional)')" />
                            <select id="payment_account_id" name="payment_account_id" x-model="accountId" class="{{ $select }}">
                                <option value="">{{ __('No account') }}</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}" @selected(old('payment_account_id') == $account->id)>
                                        {{ $account->name }} ({{ $account->type->label() }}) — Rs {{ number_format((float) $account->current_balance, 2) }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('payment_account_id')" />
                        </div>

                        <div>
                            <x-input-label for="paid_at" :value="__('Paid on')" />
                            <x-text-input id="paid_at" name="paid_at" type="date" class="mt-1 block w-full"
                                :value="old('paid_at', \App\Support\Timezones::today())" required />
                            <x-input-error class="mt-2" :messages="$errors->get('paid_at')" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="reference" :value="__('Reference (optional)')" />
                            <x-text-input id="reference" name="reference" type="text" class="mt-1 block w-full"
                                :value="old('reference')" placeholder="{{ __('Transfer or cheque number') }}" />
                        </div>
                    </div>

                    <dl class="mt-6 space-y-2 border-t border-border/60 pt-4 text-sm dark:border-white/10">
                        <div class="flex items-center justify-between">
                            <dt class="text-text-muted">{{ __('Handing over') }}</dt>
                            <dd class="font-semibold" x-text="'Rs ' + fmt(pkr())"></dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-text-muted">{{ __('Available in that account') }}</dt>
                            <dd class="font-semibold" x-text="accountId ? 'Rs ' + fmt(available()) : '—'"></dd>
                        </div>
                        <div class="flex items-center justify-between border-t border-border/60 pt-2 dark:border-white/10">
                            <dt class="text-text-muted">{{ __('Left after this payout') }}</dt>
                            <dd class="text-lg font-semibold" :class="short() ? 'text-status-cancelled' : 'text-status-paid'"
                                x-text="accountId ? 'Rs ' + fmt(available() - pkr()) : '—'"></dd>
                        </div>
                    </dl>
                    <p class="mt-2 text-xs" :class="short() && accountId ? 'text-status-cancelled' : 'text-text-muted'"
                        x-text="accountId && short() ? '{{ __('That account does not have this much — its balance will go negative.') }}' : '{{ __('The server recomputes every figure on save; this is only a preview.') }}'"></p>

                    <div class="mt-4">
                        <x-input-label for="notes" :value="__('Notes')" />
                        <textarea id="notes" name="notes" rows="2" class="{{ $select }}">{{ old('notes') }}</textarea>
                    </div>
                </x-card>

                <div class="mt-4 flex items-center gap-4">
                    <x-button variant="primary" type="submit"><x-icon name="check-circle" :size="16" /> {{ __('Record payout') }}</x-button>
                    <a href="{{ route('commissions.ledger', $dispatcher) }}" class="text-sm text-text-muted hover:underline">{{ __('Cancel') }}</a>
                </div>
                <p class="mt-2 text-xs text-text-muted">{{ __('This moves real money and is recorded permanently. A mistake is fixed with a new entry, not by editing this one.') }}</p>
            </form>
        @else
            <x-card><p class="text-sm text-text-muted">{{ __('Choose a dispatcher to see what they are owed and record a payout.') }}</p></x-card>
        @endif
    </div>
</x-app-layout>
