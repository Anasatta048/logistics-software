<x-app-layout>
    <x-slot name="header">{{ __('New Invoice') }}</x-slot>

    @php
        $field = 'mt-1 block w-full rounded-input border-border bg-surface text-text-primary shadow-sm focus:border-brand-primary focus:ring-brand-primary dark:bg-surface-dark dark:border-white/10 dark:text-white';
        $step = 'flex h-7 w-7 shrink-0 items-center justify-center bg-brand-navy text-xs font-bold text-white border-b-2 border-brand-primary';
    @endphp

    <script>
        window.invoiceForm = (cfg) => ({
            // company picker
            cq: '', companies: [], copen: false, csearching: false, company: null,
            // driver picker (limited to the chosen company)
            q: '', results: [], open: false, searching: false, driver: null,
            // range + loads
            from: cfg.weekStart, to: cfg.today, loads: [], selected: [], loading: false, loaded: false, error: null,
            // invoice details
            invoiceDate: cfg.today, dueDate: cfg.dueDate, notes: '',
            carry: { pending: 0, credit: 0, pending_invoices: [], credit_invoices: [] }, includePrevious: false, useCredit: false,
            money(value) { return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value || 0); },

            async searchCompanies() {
                this.csearching = true;
                try {
                    const res = await fetch(`${cfg.companiesUrl}?q=${encodeURIComponent(this.cq)}`, { headers: { Accept: 'application/json' } });
                    this.companies = (await res.json()).results;
                    this.copen = true;
                } finally { this.csearching = false; }
            },
            pickCompany(company) {
                this.company = company; this.copen = false; this.cq = '';
                this.clearDriver();
                this.search();
            },
            clearCompany() { this.company = null; this.clearDriver(); },

            async search() {
                if (!this.company) return;
                this.searching = true;
                try {
                    const res = await fetch(`${cfg.driversUrl}?include_inactive=1&company_id=${this.company.id}&q=${encodeURIComponent(this.q)}`, { headers: { Accept: 'application/json' } });
                    this.results = (await res.json()).data;
                    this.open = true;
                } finally { this.searching = false; }
            },
            pick(driver) {
                this.driver = driver; this.open = false; this.q = '';
                this.fetchLoads();
            },
            clearDriver() { this.driver = null; this.results = []; this.loads = []; this.selected = []; this.loaded = false; },

            iso(date) { return date.toISOString().slice(0, 10); },
            base() { return new Date(`${cfg.today}T12:00:00Z`); },
            preset(kind) {
                const today = this.base();
                const day = (today.getUTCDay() + 6) % 7; // Monday = 0
                const shift = (d, n) => new Date(d.getTime() + n * 86400000);
                if (kind === 'week') { this.from = this.iso(shift(today, -day)); this.to = this.iso(today); }
                if (kind === 'lastweek') { this.from = this.iso(shift(today, -day - 7)); this.to = this.iso(shift(today, -day - 1)); }
                if (kind === '7') { this.from = this.iso(shift(today, -6)); this.to = this.iso(today); }
                if (kind === '30') { this.from = this.iso(shift(today, -29)); this.to = this.iso(today); }
                this.fetchLoads();
            },
            async fetchLoads() {
                if (!this.driver || !this.from || !this.to) return;
                this.loading = true; this.error = null;
                try {
                    const params = new URLSearchParams({ driver_id: this.driver.id, from: this.from, to: this.to });
                    const res = await fetch(`${cfg.loadsUrl}?${params}`, { headers: { Accept: 'application/json' } });
                    if (!res.ok) throw new Error('Could not load this driver\'s loads.');
                    const json = await res.json();
                    this.loads = json.data;
                    this.carry = json.carryover || this.carry;
                    this.includePrevious = false; this.useCredit = false;
                    this.selected = this.loads.map((l) => l.id);
                    this.loaded = true;
                } catch (e) { this.error = e.message; } finally { this.loading = false; }
            },

            get chosen() { return this.loads.filter((l) => this.selected.includes(l.id)); },
            get gross() { return this.chosen.reduce((sum, l) => sum + l.rate, 0); },
            get fees() { return this.chosen.reduce((sum, l) => sum + l.fee, 0); },
            get previous() { return this.includePrevious ? this.carry.pending : 0; },
            get credit() { return this.useCredit ? Math.min(this.carry.credit, this.fees + this.previous) : 0; },
            get total() { return this.fees + this.previous - this.credit; },
            get allSelected() { return this.loads.length > 0 && this.selected.length === this.loads.length; },
            toggleAll() { this.selected = this.allSelected ? [] : this.loads.map((l) => l.id); },
            syncDue() {
                const d = new Date(`${this.invoiceDate}T12:00:00Z`);
                if (!isNaN(d)) this.dueDate = this.iso(new Date(d.getTime() + cfg.dueDays * 86400000));
            },
        });
    </script>

    <div class="form-page-head">
        <span class="form-page-icon"><x-icon name="file-text" :size="26" /></span>
        <div class="min-w-0 flex-1">
            <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Create an invoice') }}</h1>
            <p class="mt-0.5 text-sm text-text-muted">{{ __('Pick the company and driver, choose the week, tick the loads — the total builds itself.') }}</p>
        </div>
        <a href="{{ route('invoices.index') }}" class="btn-ghost shrink-0"><x-icon name="arrow-left" :size="15" /> {{ __('All invoices') }}</a>
    </div>

    <form method="POST" action="{{ route('invoices.store') }}"
        x-data="invoiceForm({
            companiesUrl: @js(route('companies.search')),
            driversUrl: @js(route('drivers.search')),
            loadsUrl: @js(route('invoices.loads')),
            today: @js($today), weekStart: @js($weekStart), dueDate: @js($dueDate),
            dueDays: {{ (int) config('invoice.due_days') }},
        })"
        class="form-anim grid grid-cols-1 items-start gap-5 lg:grid-cols-3">
        @csrf
        <input type="hidden" name="driver_id" :value="driver?.id">
        <input type="hidden" name="period_from" :value="from">
        <input type="hidden" name="period_to" :value="to">
        <template x-for="id in selected" :key="id"><input type="hidden" name="load_ids[]" :value="id"></template>

        <div class="space-y-6 lg:col-span-2">
            {{-- 1. Company --}}
            <x-card class="space-y-4">
                <div class="flex items-center gap-3">
                    <span class="{{ $step }}">1</span>
                    <div>
                        <h2 class="text-sm font-semibold text-text-primary dark:text-white">{{ __('Choose the company') }}</h2>
                        <p class="text-xs text-text-muted">{{ __('The company that owes the commission and receives the invoice.') }}</p>
                    </div>
                </div>

                <div x-show="!company" class="relative" x-on:click.outside="copen = false">
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-text-muted"><x-icon name="search" :size="16" /></span>
                        <input type="search" x-model="cq" x-on:input.debounce.250ms="searchCompanies()" x-on:focus="searchCompanies()"
                            placeholder="{{ __('Search by company name or MC number…') }}" autocomplete="off"
                            class="{{ $field }} pl-9">
                    </div>
                    <ul x-show="copen" x-cloak
                        class="absolute z-20 mt-1 max-h-72 w-full overflow-auto rounded-card border border-border bg-surface py-1 shadow-lg dark:border-white/10 dark:bg-surface-dark">
                        <template x-for="c in companies" :key="c.id">
                            <li>
                                <button type="button" x-on:click="pickCompany(c)"
                                    class="flex w-full flex-col px-3 py-2 text-left hover:bg-bg dark:hover:bg-white/5">
                                    <span class="text-sm font-medium text-text-primary dark:text-white" x-text="c.name"></span>
                                    <span class="text-xs text-text-muted" x-text="`MC ${c.mc_number}`"></span>
                                </button>
                            </li>
                        </template>
                        <li x-show="companies.length === 0 && !csearching" class="px-3 py-3 text-sm text-text-muted">{{ __('No companies found.') }}</li>
                    </ul>
                </div>

                <div x-show="company" x-cloak class="flex items-center justify-between gap-3 rounded-card border border-border/70 bg-bg/50 px-4 py-3 dark:border-white/10 dark:bg-white/5">
                    <div>
                        <div class="text-sm font-semibold text-text-primary dark:text-white" x-text="company?.name"></div>
                        <div class="text-xs text-text-muted">MC <span x-text="company?.mc_number"></span></div>
                    </div>
                    <button type="button" class="text-xs font-semibold text-brand-primary hover:underline" x-on:click="clearCompany()">{{ __('Change') }}</button>
                </div>
            </x-card>

            {{-- 1. Driver --}}
            <x-card class="space-y-4" x-bind:class="!company && 'opacity-60'">
                <div class="flex items-center gap-3">
                    <span class="{{ $step }}">2</span>
                    <div>
                        <h2 class="text-sm font-semibold text-text-primary dark:text-white">{{ __('Choose the driver') }}</h2>
                        <p class="text-xs text-text-muted">{{ __('One invoice covers one driver of this company.') }}</p>
                    </div>
                </div>

                <div x-show="company && !driver" class="relative" x-on:click.outside="open = false">
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-text-muted"><x-icon name="search" :size="16" /></span>
                        <input type="search" x-model="q" x-on:input.debounce.250ms="search()" x-on:focus="search()"
                            placeholder="{{ __('Search by driver name, phone or truck…') }}" autocomplete="off"
                            class="{{ $field }} pl-9">
                    </div>
                    <ul x-show="open" x-cloak
                        class="absolute z-20 mt-1 max-h-72 w-full overflow-auto rounded-card border border-border bg-surface py-1 shadow-lg dark:border-white/10 dark:bg-surface-dark">
                        <template x-for="d in results" :key="d.id">
                            <li>
                                <button type="button" x-on:click="pick(d)"
                                    class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left hover:bg-bg dark:hover:bg-white/5">
                                    <span>
                                        <span class="block text-sm font-medium text-text-primary dark:text-white" x-text="d.name"></span>
                                        <span class="block text-xs text-text-muted" x-text="`${d.phone} · Truck ${d.truck_number}`"></span>
                                    </span>
                                    <span x-show="d.status === 'inactive'" class="lookup-badge">Inactive</span>
                                </button>
                            </li>
                        </template>
                        <li x-show="results.length === 0 && !searching" class="px-3 py-3 text-sm text-text-muted">{{ __('No drivers found.') }}</li>
                    </ul>
                </div>
                <p x-show="!company" class="text-sm text-text-muted">{{ __('Choose a company first and its drivers will be listed here.') }}</p>

                <div x-show="driver" x-cloak class="flex items-center justify-between gap-3 rounded-card border border-border/70 bg-bg/50 px-4 py-3 dark:border-white/10 dark:bg-white/5">
                    <div>
                        <div class="text-sm font-semibold text-text-primary dark:text-white" x-text="driver?.name"></div>
                        <div class="text-xs text-text-muted" x-text="`${driver?.phone ?? ''} · Truck ${driver?.truck_number ?? ''}`"></div>
                    </div>
                    <button type="button" class="text-xs font-semibold text-brand-primary hover:underline" x-on:click="clearDriver()">{{ __('Change') }}</button>
                </div>
                <x-input-error :messages="$errors->get('driver_id')" />
            </x-card>

            {{-- 2. Range + loads --}}
            <x-card class="space-y-4" x-bind:class="!driver && 'opacity-60'">
                <div class="flex items-center gap-3">
                    <span class="{{ $step }}">3</span>
                    <div>
                        <h2 class="text-sm font-semibold text-text-primary dark:text-white">{{ __('Pick the dates and loads') }}</h2>
                        <p class="text-xs text-text-muted">{{ __('Delivered or paid loads that are not on another invoice, by pickup date.') }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="text-xs font-medium text-text-muted" for="range_from">{{ __('From') }}</label>
                        <input id="range_from" type="date" x-model="from" x-on:change="fetchLoads()" class="{{ $field }}">
                    </div>
                    <div>
                        <label class="text-xs font-medium text-text-muted" for="range_to">{{ __('To') }}</label>
                        <input id="range_to" type="date" x-model="to" x-on:change="fetchLoads()" class="{{ $field }}">
                    </div>
                    <div class="flex flex-wrap gap-2 pb-0.5">
                        @foreach (['week' => 'This week', 'lastweek' => 'Last week', '7' => 'Last 7 days', '30' => 'Last 30 days'] as $key => $label)
                            <button type="button" x-on:click="preset('{{ $key }}')"
                                class="rounded-full border border-border px-3 py-1 text-xs font-medium text-text-muted transition hover:border-brand-primary hover:text-brand-primary dark:border-white/10">
                                {{ __($label) }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <p x-show="error" x-text="error" class="text-sm text-status-cancelled" x-cloak></p>
                <p x-show="!driver" class="rounded-card border border-dashed border-border px-4 py-6 text-center text-sm text-text-muted dark:border-white/10">
                    {{ __('Choose a driver first and their loads will appear here.') }}
                </p>
                <p x-show="driver && loading" x-cloak class="px-1 py-4 text-sm text-text-muted">{{ __('Loading loads…') }}</p>
                <p x-show="driver && loaded && !loading && loads.length === 0" x-cloak
                    class="rounded-card border border-dashed border-border px-4 py-6 text-center text-sm text-text-muted dark:border-white/10">
                    {{ __('No invoiceable loads for this driver in these dates.') }}
                </p>

                <div x-show="loads.length > 0 && !loading" x-cloak class="overflow-x-auto rounded-card border border-border/70 dark:border-white/10">
                    <table class="min-w-full text-sm">
                        <thead class="bg-bg/60 text-left text-xs uppercase tracking-wider text-text-muted dark:bg-white/5">
                            <tr>
                                <th class="w-10 px-3 py-2">
                                    <input type="checkbox" class="rounded border-border text-brand-primary focus:ring-brand-primary"
                                        :checked="allSelected" x-on:change="toggleAll()" aria-label="Select all">
                                </th>
                                <th class="px-3 py-2">{{ __('Load') }}</th>
                                <th class="px-3 py-2">{{ __('Route') }}</th>
                                <th class="px-3 py-2 text-right">{{ __('Load rate') }}</th>
                                <th class="px-3 py-2 text-right">%</th>
                                <th class="px-3 py-2 text-right">{{ __('Fee') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            <template x-for="load in loads" :key="load.id">
                                <tr class="cursor-pointer transition hover:bg-bg/60 dark:hover:bg-white/5"
                                    :class="selected.includes(load.id) && 'bg-brand-primary/5'"
                                    x-on:click="selected.includes(load.id) ? selected = selected.filter(i => i !== load.id) : selected.push(load.id)">
                                    <td class="px-3 py-2" x-on:click.stop>
                                        <input type="checkbox" class="rounded border-border text-brand-primary focus:ring-brand-primary"
                                            :value="load.id" x-model="selected">
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        <span class="block font-medium text-text-primary dark:text-white" x-text="load.reference"></span>
                                        <span class="block text-xs text-text-muted" x-text="load.date"></span>
                                    </td>
                                    <td class="px-3 py-2 text-text-muted" x-text="load.route"></td>
                                    <td class="px-3 py-2 text-right whitespace-nowrap text-text-muted" x-text="money(load.rate)"></td>
                                    <td class="px-3 py-2 text-right text-text-muted" x-text="load.percentage + '%'"></td>
                                    <td class="px-3 py-2 text-right font-semibold whitespace-nowrap text-text-primary dark:text-white" x-text="money(load.fee)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <x-input-error :messages="$errors->get('load_ids')" />
                <x-input-error :messages="$errors->get('period_to')" />
            </x-card>
        </div>

        {{-- 3. Summary --}}
        <div class="lg:sticky lg:top-6">
            <x-card class="space-y-5">
                <div class="flex items-center gap-3">
                    <span class="{{ $step }}">4</span>
                    <h2 class="text-sm font-semibold text-text-primary dark:text-white">{{ __('Invoice details') }}</h2>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium text-text-muted" for="invoice_date">{{ __('Invoice date') }}</label>
                        <input id="invoice_date" name="invoice_date" type="date" required x-model="invoiceDate" x-on:change="syncDue()" class="{{ $field }}">
                        <x-input-error class="mt-1" :messages="$errors->get('invoice_date')" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-text-muted" for="due_date">{{ __('Due date') }}</label>
                        <input id="due_date" name="due_date" type="date" required x-model="dueDate" class="{{ $field }}">
                        <x-input-error class="mt-1" :messages="$errors->get('due_date')" />
                    </div>
                </div>

                <div>
                    <label class="text-xs font-medium text-text-muted" for="notes">{{ __('Notes (optional)') }}</label>
                    <textarea id="notes" name="notes" rows="2" x-model="notes" class="{{ $field }}">{{ old('notes') }}</textarea>
                </div>

                <dl class="space-y-2 border-t border-border/60 pt-4 text-sm dark:border-white/10">
                    <div class="flex justify-between"><dt class="text-text-muted">{{ __('Loads selected') }}</dt><dd class="font-medium" x-text="selected.length"></dd></div>
                    <div class="flex justify-between"><dt class="text-text-muted">{{ __('Total weekly gross') }}</dt><dd class="text-text-muted" x-text="money(gross)"></dd></div>
                    <div class="flex justify-between"><dt class="text-text-muted">{{ __('Commission on these loads') }}</dt><dd class="font-medium" x-text="money(fees)"></dd></div>
                </dl>

                {{-- Optional: this company's earlier unpaid balance and advance credit. --}}
                <div x-show="carry.pending > 0 || carry.credit > 0" x-cloak class="space-y-3 rounded-card border border-dashed border-brand-primary/50 bg-brand-primary/5 p-3 text-sm">
                    <div class="text-xs font-semibold uppercase tracking-wider text-brand-primary">{{ __('From earlier invoices') }}</div>
                    <label x-show="carry.pending > 0" class="flex cursor-pointer items-start gap-2">
                        <input type="checkbox" name="include_previous" value="1" x-model="includePrevious"
                            class="mt-0.5 rounded border-border text-brand-primary focus:ring-brand-primary">
                        <span>
                            <span class="block font-medium text-text-primary dark:text-white">
                                {{ __('Add previous pending') }} <span x-text="money(carry.pending)"></span>
                            </span>
                            <span class="block text-xs text-text-muted" x-text="'Invoice ' + carry.pending_invoices.map(i => '#' + i.number).join(', ')"></span>
                        </span>
                    </label>
                    <label x-show="carry.credit > 0" class="flex cursor-pointer items-start gap-2">
                        <input type="checkbox" name="use_credit" value="1" x-model="useCredit"
                            class="mt-0.5 rounded border-border text-brand-primary focus:ring-brand-primary">
                        <span>
                            <span class="block font-medium text-text-primary dark:text-white">
                                {{ __('Deduct advance credit') }} <span x-text="money(carry.credit)"></span>
                            </span>
                            <span class="block text-xs text-text-muted">{{ __('Paid in excess earlier. Leave unticked to keep it for later.') }}</span>
                        </span>
                    </label>
                    <div x-show="previous > 0" class="flex justify-between text-xs text-text-muted"><span>{{ __('Previous pending added') }}</span><span x-text="'+ ' + money(previous)"></span></div>
                    <div x-show="credit > 0" class="flex justify-between text-xs text-text-muted"><span>{{ __('Advance credit deducted') }}</span><span x-text="'− ' + money(credit)"></span></div>
                </div>

                <div class="rounded-card bg-brand-primary/10 px-4 py-3">
                    <div class="text-xs font-semibold uppercase tracking-wider text-brand-primary">{{ __('Total due') }}</div>
                    <div class="mt-1 text-3xl font-bold text-text-primary dark:text-white" x-text="money(total)"></div>
                </div>

                <x-button variant="primary" type="submit" class="w-full" x-bind:disabled="!driver || selected.length === 0">
                    {{ __('Create Invoice (Pending)') }}
                </x-button>
                <a href="{{ route('invoices.index') }}" class="block text-center text-sm text-text-muted hover:underline">{{ __('Cancel') }}</a>
            </x-card>
        </div>
    </form>
</x-app-layout>
