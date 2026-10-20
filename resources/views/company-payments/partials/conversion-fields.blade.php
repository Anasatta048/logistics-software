@php
    /**
     * The PKR conversion fields, shared by the payment form (optional section)
     * and the payment page ("Convert to PKR" for a payment recorded earlier).
     * The parent scope supplies `amount`, the USD being converted.
     *
     * @var \Illuminate\Support\Collection $accounts  active accounts
     * @var float|null $rate  last rate used
     * @var bool $askAmount  show an "amount to convert" box (payment page only)
     * @var float|null $maxUsd
     */
    $askAmount ??= false;
    $taxRules = \App\Models\TaxRule::query()->active()->get(['id', 'name', 'type', 'value']);
    $defaultRule = $taxRules->firstWhere('id', $taxRules->firstWhere('is_default', true)?->id ?? 0);
    $maxUsd ??= null;
    $field = 'mt-1 block w-full rounded-input border-border bg-surface text-text-primary shadow-sm focus:border-brand-primary focus:ring-brand-primary dark:bg-surface-dark dark:border-white/10 dark:text-white';
@endphp

<div x-data="{
        rate: @js(old('conversion_rate', $rate)),
        accountId: @js(old('deposit_account_id')),
        taxOn: @js((bool) old('tax_applicable', false)),
        taxType: @js(old('tax_type', $defaultRule?->type ?? 'percentage')),
        taxValue: @js(old('tax_value', $defaultRule ? (float) $defaultRule->value : '')),
        rules: @js($taxRules->map(fn ($r) => ['id' => $r->id, 'type' => $r->type, 'value' => (float) $r->value])->values()),
        pick(id) { const r = this.rules.find(x => x.id == id); if (r) { this.taxType = r.type; this.taxValue = r.value; } },
        balances: @js($accounts->pluck('current_balance', 'id')->all()),
        @if ($askAmount) usd: @js(old('usd_amount', $maxUsd)), @endif
        get base() { return {{ $askAmount ? 'Number(this.usd) || 0' : 'Number(amount) || 0' }}; },
        get gross() { return this.base * (Number(this.rate) || 0); },
        get tax() {
            if (! this.taxOn) return 0;
            const v = Number(this.taxValue) || 0;
            return this.taxType === 'fixed' ? v : this.gross * v / 100;
        },
        get net() { return Math.max(0, this.gross - this.tax); },
        get after() { return this.accountId ? Number(this.balances[this.accountId] || 0) + this.net : null; },
        rs(v) { return 'Rs ' + Number(v || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    }" class="space-y-4">

    <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
        @if ($askAmount)
            <div>
                <x-input-label for="usd_amount" :value="__('Amount to convert (USD)')" />
                <x-text-input id="usd_amount" name="usd_amount" type="number" step="0.01" min="0.01"
                    :max="$maxUsd" class="mt-1 block w-full" x-model="usd" required />
                <p class="mt-1 text-xs text-text-muted">{{ __('Up to what was received. Converting less leaves the rest in USD.') }}</p>
                <x-input-error class="mt-2" :messages="$errors->get('usd_amount')" />
            </div>
        @endif

        <div>
            <x-input-label for="conversion_rate" :value="__('USD → PKR rate')" />
            <x-text-input id="conversion_rate" name="conversion_rate" type="number" step="0.0001" min="0.0001"
                class="mt-1 block w-full" x-model="rate" required />
            <p class="mt-1 text-xs text-text-muted">
                {{ $rate ? __('Pre-filled from the last rate used — change it to today\'s rate.') : __('Type the rate you actually got.') }}
            </p>
            <x-input-error class="mt-2" :messages="$errors->get('conversion_rate')" />
        </div>

        <div>
            <x-input-label for="deposit_account_id" :value="__('Deposit into (optional)')" />
            <select id="deposit_account_id" name="deposit_account_id" x-model="accountId" class="{{ $field }}">
                <option value="">{{ __('No account') }}</option>
                @foreach ($accounts as $account)
                    <option value="{{ $account->id }}" @selected(old('deposit_account_id') == $account->id)>
                        {{ $account->name }} ({{ $account->type->label() }})
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('deposit_account_id')" />
        </div>

        <div>
            <x-input-label for="conversion_date" :value="__('Converted on')" />
            <x-text-input id="conversion_date" name="conversion_date" type="date" class="mt-1 block w-full"
                :value="old('conversion_date', \App\Support\Timezones::today())" />
            <x-input-error class="mt-2" :messages="$errors->get('conversion_date')" />
        </div>
    </div>

    {{-- Tax --}}
    <div class="rounded-input border border-border/70 p-3 dark:border-white/10">
        <label class="flex items-center gap-2 text-sm font-medium text-text-primary dark:text-white">
            <input type="hidden" name="tax_applicable" value="0">
            <input type="checkbox" name="tax_applicable" value="1" x-model="taxOn"
                class="rounded border-border text-brand-primary focus:ring-brand-primary">
            {{ __('Tax was deducted') }}
        </label>

        <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2" x-show="taxOn" x-cloak>
            @if ($taxRules->isNotEmpty())
                <div class="sm:col-span-2">
                    <x-input-label for="tax_rule" :value="__('Tax rule')" />
                    <select id="tax_rule" class="{{ $field }}" @change="pick($event.target.value)">
                        <option value="">{{ __('Choose a saved rule…') }}</option>
                        @foreach ($taxRules as $r)
                            <option value="{{ $r->id }}" @selected($defaultRule?->id === $r->id)>{{ $r->name }} ({{ $r->summary() }})</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div>
                <x-input-label for="tax_type" :value="__('Tax type')" />
                <select id="tax_type" name="tax_type" x-model="taxType" :disabled="! taxOn" class="{{ $field }}">
                    @foreach (\App\Enums\TaxType::cases() as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('tax_type')" />
            </div>
            <div>
                <x-input-label for="tax_value" :value="__('Tax value')" />
                <x-text-input id="tax_value" name="tax_value" type="number" step="0.01" min="0"
                    class="mt-1 block w-full" x-model="taxValue" x-bind:disabled="! taxOn" />
                <p class="mt-1 text-xs text-text-muted" x-text="taxType === 'fixed' ? '{{ __('A fixed amount in PKR.') }}' : '{{ __('A percentage of the gross PKR.') }}'"></p>
                <x-input-error class="mt-2" :messages="$errors->get('tax_value')" />
            </div>
        </div>
    </div>

    {{-- Live PKR preview --}}
    <dl class="space-y-2 rounded-input bg-bg/70 p-3 text-sm dark:bg-white/5">
        <div class="flex items-center justify-between">
            <dt class="text-text-muted">{{ __('Gross PKR') }}</dt>
            <dd class="font-medium" x-text="rs(gross)"></dd>
        </div>
        <div class="flex items-center justify-between" x-show="taxOn" x-cloak>
            <dt class="text-text-muted">{{ __('Tax') }}</dt>
            <dd class="font-medium text-status-cancelled" x-text="'− ' + rs(tax)"></dd>
        </div>
        <div class="flex items-center justify-between border-t border-border/60 pt-2 dark:border-white/10">
            <dt class="font-medium text-text-primary dark:text-white">{{ __('Net deposited') }}</dt>
            <dd class="text-base font-semibold text-status-paid" x-text="rs(net)"></dd>
        </div>
        <div class="flex items-center justify-between text-xs" x-show="accountId" x-cloak>
            <dt class="text-text-muted">{{ __('Account balance after') }}</dt>
            <dd class="text-text-muted" x-text="after === null ? '—' : rs(after)"></dd>
        </div>
    </dl>

    <div>
        <x-input-label for="conversion_notes" :value="__('Notes (optional)')" />
        <textarea id="conversion_notes" name="conversion_notes" rows="2" class="{{ $field }}">{{ old('conversion_notes') }}</textarea>
    </div>
</div>
