@props([
    'action',
    'brokers' => null,
    'drivers' => null,
    'companies' => null,
    'accounts' => null,
    'accountsAll' => false,
    'categories' => null,
    'users' => null,
    'statuses' => null,
    'statusLabel' => 'Status',
    'selectedAccountId' => null,
    'exportable' => false,
    'dates' => true,
])

@php
    $select = 'mt-1 block rounded-input border-border bg-surface text-text-primary shadow-sm focus:border-brand-primary focus:ring-brand-primary dark:bg-surface-dark dark:border-white/10 dark:text-white';
@endphp

<form method="GET" action="{{ route($action) }}" class="flex flex-wrap items-end gap-3" onchange="this.requestSubmit()">
    @if ($dates)
        <div>
            <x-input-label for="from" :value="__('From')" />
            <x-text-input id="from" name="from" type="date" class="mt-1 block w-40" :value="request('from')" />
        </div>
        <div>
            <x-input-label for="to" :value="__('To')" />
            <x-text-input id="to" name="to" type="date" class="mt-1 block w-40" :value="request('to')" />
        </div>
    @endif

    @if ($dates)
        @php
            $today = \Illuminate\Support\Carbon::today();
            $presets = [
                __('Today') => [$today, $today],
                __('This week') => [$today->copy()->startOfWeek(week_start_day()), $today],
                __('This month') => [$today->copy()->startOfMonth(), $today],
                __('Last 30 days') => [$today->copy()->subDays(29), $today],
                __('Financial year') => [fiscal_year_start(), $today],
            ];
        @endphp
        <div class="flex flex-wrap items-center gap-1 self-end pb-1 text-xs">
            @foreach ($presets as $label => [$pf, $pt])
                <a href="{{ route($action, array_merge(request()->except(['export', 'from', 'to']), ['from' => $pf->toDateString(), 'to' => $pt->toDateString()])) }}"
                   class="border border-border px-2 py-1 font-semibold text-text-muted hover:border-brand-primary hover:text-brand-primary dark:border-white/10">{{ $label }}</a>
            @endforeach
        </div>
    @endif

    @if ($statuses)
        <div>
            <x-input-label for="status" :value="__($statusLabel)" />
            <select id="status" name="status" class="{{ $select }}">
                <option value="">{{ __('All') }}</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected((string) request('status') === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    @endif

    @if ($companies)
        <div>
            <x-input-label for="company_id" :value="__('Company')" />
            <select id="company_id" name="company_id" class="{{ $select }}">
                <option value="">{{ __('All') }}</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    @if ($brokers)
        <div>
            <x-input-label for="broker_id" :value="__('Broker')" />
            <select id="broker_id" name="broker_id" class="{{ $select }}">
                <option value="">{{ __('All') }}</option>
                @foreach ($brokers as $broker)
                    <option value="{{ $broker->id }}" @selected((string) request('broker_id') === (string) $broker->id)>{{ $broker->company_name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    @if ($drivers)
        <div>
            <x-input-label for="driver_id" :value="__('Driver')" />
            <select id="driver_id" name="driver_id" class="{{ $select }}">
                <option value="">{{ __('All') }}</option>
                @foreach ($drivers as $driver)
                    <option value="{{ $driver->id }}" @selected((string) request('driver_id') === (string) $driver->id)>{{ $driver->name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    @if ($users)
        <div>
            <x-input-label for="user_id" :value="__('Dispatcher')" />
            <select id="user_id" name="user_id" class="{{ $select }}">
                <option value="">{{ __('All') }}</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    @if ($accounts)
        <div>
            <x-input-label for="account_id" :value="__('Account')" />
            <select id="account_id" name="account_id" class="{{ $select }}">
                @if ($accountsAll)<option value="">{{ __('All') }}</option>@endif
                @foreach ($accounts as $account)
                    <option value="{{ $account->id }}" @selected((string) ($selectedAccountId ?? request('account_id')) === (string) $account->id)>{{ $account->name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    @if ($categories)
        <div>
            <x-input-label for="expense_category_id" :value="__('Category')" />
            <select id="expense_category_id" name="expense_category_id" class="{{ $select }}">
                <option value="">{{ __('All') }}</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('expense_category_id') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <a href="{{ route($action) }}" class="btn-ghost btn-sm h-[38px]"><x-icon name="refresh" :size="14" /> {{ __('Reset') }}</a>

    @if ($exportable)
        <div class="ml-auto flex gap-2">
            <a data-no-loader href="{{ route($action, array_merge(request()->query(), ['export' => 'csv'])) }}" class="btn-ghost btn-sm h-[38px]"><x-icon name="download" :size="14" /> CSV</a>
            <a data-no-loader href="{{ route($action, array_merge(request()->query(), ['export' => 'pdf'])) }}" class="btn-primary btn-sm h-[38px]"><x-icon name="printer" :size="14" /> PDF</a>
        </div>
    @endif
</form>
