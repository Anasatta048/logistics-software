<x-app-layout>
    <x-slot name="header">{{ __('Billing') }}</x-slot>

    @php
        $money = fn (float $amount, string $currency) => ($currency === 'PKR' ? 'Rs ' : '$').number_format($amount, $currency === 'PKR' ? 0 : 2);
        $planData = $plans->mapWithKeys(fn ($p) => [$p->id => [
            'monthly' => $p->charge('monthly'),
            'yearly' => $p->charge('yearly'),
            'saving' => $p->yearlySavingPercent(),
        ]]);
    @endphp

    <div class="space-y-5" x-data="{ plan: '{{ old('plan_id', $tenant->plan_id) }}', cycle: '{{ old('billing_cycle', $tenant->billing_cycle) }}', plans: @js($planData),
        get due() { const p = this.plans[this.plan]; if (!p) return ''; const c = p[this.cycle]; return (c.currency === 'PKR' ? 'Rs ' : '$') + Number(c.amount).toLocaleString(); } }">
        <div class="form-page-head !mb-0 flex-wrap">
            <span class="form-page-icon"><x-icon name="wallet" :size="26" /></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-extrabold tracking-tight text-text-primary dark:text-white">{{ __('Billing') }}</h1>
                <p class="text-sm text-text-muted">{{ __('Your plan, renewals and receipts.') }}</p>
            </div>
        </div>

        @if (session('success'))<x-alert variant="success">{{ session('success') }}</x-alert>@endif
        @if ($errors->any())<x-alert variant="error">{{ $errors->first() }}</x-alert>@endif

        {{-- Where the subscription stands --}}
        <x-card>
            <div class="flex flex-wrap items-center gap-3">
                <span class="px-2.5 py-1 text-xs font-bold {{ $tenant->status->color() }}">{{ $tenant->status->label() }}</span>
                <span class="text-lg font-extrabold text-text-primary dark:text-white">{{ $tenant->plan?->name ?? __('No plan') }}</span>
                <span class="text-sm text-text-muted">{{ ucfirst($tenant->billing_cycle) }}</span>
                @if ($tenant->endsAt())
                    <span class="ml-auto text-sm text-text-muted">{{ $tenant->status->value === 'trial' ? __('Trial ends') : __('Paid until') }}
                        <strong class="text-text-primary dark:text-white">{{ $tenant->endsAt()->format(df()) }}</strong>
                        ({{ $tenant->endsAt()->diffForHumans() }})</span>
                @endif
            </div>
            @if (! $tenant->isUsable())
                <p class="mt-3 text-sm text-warning">{{ $tenant->lockedReason() }} {{ __('Pay below to unlock your workspace.') }}</p>
            @elseif ($tenant->status->value === 'active' && $tenant->subscription_ends_at?->isPast())
                <p class="mt-3 text-sm text-warning">{{ __('Your period has ended. You are in the :days-day grace window: renew now to avoid being locked out.', ['days' => \App\Models\Tenant::GRACE_DAYS]) }}</p>
            @endif
        </x-card>

        <div class="grid gap-5 lg:grid-cols-5">
            {{-- Pay --}}
            <x-card class="lg:col-span-3">
                <h2 class="text-base font-extrabold text-text-primary dark:text-white">{{ __('Renew or change plan') }}</h2>

                @if ($methods === [])
                    <p class="mt-3 text-sm text-text-muted">{{ __('Online payment is not set up yet. Contact :email to renew.', ['email' => config('saas.support_email')]) }}</p>
                @else
                    <form method="POST" action="{{ route('billing.payments.store') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                        @csrf
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-field name="plan_id" :label="__('Plan')" icon="tag" required>
                                <select id="plan_id" name="plan_id" x-model="plan">
                                    @foreach ($plans as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                                </select>
                            </x-field>
                            <x-field name="billing_cycle" :label="__('Billing cycle')" icon="calendar" required>
                                <select id="billing_cycle" name="billing_cycle" x-model="cycle">
                                    <option value="monthly">{{ __('Monthly') }}</option>
                                    <option value="yearly">{{ __('Yearly') }}</option>
                                </select>
                            </x-field>
                        </div>
                        <p class="text-sm">{{ __('Amount to pay') }}: <strong class="text-xl text-brand-primary" x-text="due"></strong>
                            <span class="text-xs text-status-paid" x-show="cycle === 'yearly' && plans[plan] && plans[plan].saving > 0" x-cloak x-text="'· ' + (plans[plan]?.saving) + '% cheaper than 12 months'"></span></p>

                        <x-field name="method" :label="__('Paid via')" icon="wallet" required>
                            <select id="method" name="method" x-ref="method">
                                @foreach ($methods as $key => $label)<option value="{{ $key }}" @selected(old('method') === $key)>{{ __($label) }}</option>@endforeach
                            </select>
                        </x-field>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-field name="reference" :label="__('Transaction / reference no.')" icon="hash"><input id="reference" name="reference" type="text" value="{{ old('reference') }}" maxlength="120"></x-field>
                            <x-field name="proof" :label="__('Payment slip / screenshot')" icon="upload" required :hint="__('PDF, JPG or PNG, up to 5 MB.')"><input id="proof" name="proof" type="file" accept=".pdf,.jpg,.jpeg,.png" required></x-field>
                        </div>
                        <x-field name="note" :label="__('Note (optional)')" icon="note"><input id="note" name="note" type="text" value="{{ old('note') }}" maxlength="1000"></x-field>
                        <button class="btn-primary"><x-icon name="check" :size="16" /> {{ __('Submit payment') }}</button>
                    </form>
                @endif
            </x-card>

            {{-- Where to send the money --}}
            <x-card class="lg:col-span-2">
                <h2 class="text-base font-extrabold text-text-primary dark:text-white">{{ __('Send payment to') }}</h2>
                <dl class="mt-3 space-y-3 text-sm">
                    @if (isset($methods['bank_transfer']))
                        <div><dt class="text-xs font-bold uppercase text-text-muted">{{ __('Bank transfer') }}</dt>
                            <dd>{{ $billing['bank_name'] }}<br>{{ $billing['account_title'] }}<br>{{ $billing['account_number'] }}@if ($billing['iban'])<br>IBAN {{ $billing['iban'] }}@endif</dd></div>
                    @endif
                    @if (isset($methods['raast']))<div><dt class="text-xs font-bold uppercase text-text-muted">Raast ID</dt><dd>{{ $billing['raast_id'] }}</dd></div>@endif
                    @if (isset($methods['jazzcash']))<div><dt class="text-xs font-bold uppercase text-text-muted">JazzCash</dt><dd>{{ $billing['jazzcash_number'] }}</dd></div>@endif
                    @if (isset($methods['easypaisa']))<div><dt class="text-xs font-bold uppercase text-text-muted">Easypaisa</dt><dd>{{ $billing['easypaisa_number'] }}</dd></div>@endif
                </dl>
                <p class="mt-4 text-xs text-text-muted">{{ $billing['instructions'] }}</p>
            </x-card>
        </div>

        {{-- History --}}
        <x-card :padded="false">
            <div class="border-b border-border px-4 py-3 text-sm font-extrabold text-text-primary dark:border-white/10 dark:text-white">{{ __('Payment history') }}</div>
            @if ($payments->isEmpty())
                <x-empty-state icon="wallet" title="No payments yet" description="Payments you report and their receipts appear here." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                        <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                            <tr><th class="px-4 py-3">{{ __('Date') }}</th><th class="px-4 py-3">{{ __('Plan') }}</th><th class="px-4 py-3">{{ __('Amount') }}</th><th class="px-4 py-3">{{ __('Via') }}</th><th class="px-4 py-3">{{ __('Status') }}</th><th class="px-4 py-3"></th></tr>
                        </thead>
                        <tbody class="divide-y divide-border/60 dark:divide-white/5">
                            @foreach ($payments as $p)
                                <tr>
                                    <td class="px-4 py-3 text-text-muted">{{ $p->created_at->format(df()) }}</td>
                                    <td class="px-4 py-3">{{ $p->plan?->name }} <span class="text-xs text-text-muted">{{ $p->billing_cycle }}</span></td>
                                    <td class="px-4 py-3 font-semibold">{{ $p->money() }}</td>
                                    <td class="px-4 py-3">{{ __($p->methodLabel()) }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 text-[11px] font-bold {{ ['pending' => 'bg-brand-gold/15 text-brand-gold', 'approved' => 'bg-status-paid/15 text-status-paid', 'rejected' => 'bg-status-cancelled/15 text-status-cancelled'][$p->status] }}">{{ ucfirst($p->status) }}</span>
                                        @if ($p->status === 'rejected' && $p->review_note)<p class="mt-1 text-xs text-text-muted">{{ $p->review_note }}</p>@endif
                                        @if ($p->isApproved())<p class="mt-1 text-xs text-text-muted">{{ __('until') }} {{ $p->period_end?->format(df()) }}</p>@endif
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        @if ($p->isApproved())<a href="{{ route('billing.payments.receipt', $p) }}" class="btn-ghost btn-sm"><x-icon name="download" :size="14" /> {{ $p->receipt_number }}</a>@endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
</x-app-layout>
