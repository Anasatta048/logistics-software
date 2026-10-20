@php
    // Ordered the way the business runs: a load is booked, invoiced, paid for,
    // converted to PKR, spent from accounts, and its commission paid out.
    $sections = [
        ['Loads', 'From booking to delivery.', [
            ['reports.loads', 'reports.loads', 'Load Register', 'Every load in the window with status, route, rate and miles. Cancelled loads show who cancelled and why.'],
            ['reports.drivers', 'reports.drivers', 'Driver Earnings', 'Per driver: loads hauled, miles, gross rate and the company commission.'],
            ['reports.brokers', 'reports.brokers', 'Broker Performance', 'Per broker: load volume, paid revenue and how often loads fall through.'],
        ]],
        ['Invoices & payments', 'What was billed, what arrived, and what it became in PKR.', [
            ['reports.invoices', 'reports.invoices', 'Invoice Register', 'Every invoice with billed, paid and balance, and how overdue it is.'],
            ['reports.receivables', 'reports.receivables', 'Company Receivables', 'Per company: billed, received, balance due, overdue, credit held and commission not yet invoiced.'],
            ['reports.receipts', 'reports.receipts', 'Payment Receipts', 'Every payment received, what the company owed at the time and any shortfall.'],
            ['reports.conversions', 'reports.conversions', 'PKR Conversions', 'USD → PKR conversions with rate, tax and the account the money landed in.'],
        ]],
        ['Accounts', 'Where the money went.', [
            ['reports.expenses', 'reports.expenses', 'Expenses', 'Spending by category, with each category\'s share of the total.'],
            ['reports.account-statement', 'reports.accountStatement', 'Account Statement', 'Every transaction on one account, with a running balance.'],
        ]],
        ['Commissions & summary', 'What dispatchers earned, and what is left for the business.', [
            ['reports.dispatcher-commissions', 'reports.dispatcherCommissions', 'Dispatcher Commissions', 'Per dispatcher: commission earned, already paid, still payable and payouts made.'],
            ['reports.profit-and-loss', 'reports.profitAndLoss', 'Profit & Loss', 'Company commission billed on paid loads, minus expenses.'],
        ]],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">{{ __('Reports') }}</x-slot>

    <div class="space-y-8">
        <div>
            <h1 class="text-lg font-semibold text-text-primary dark:text-white">{{ __('Reports') }}</h1>
            <p class="text-sm text-text-muted">
                {{ __('Grouped by the stage of the business they report on. Every report filters by date range, and the ones with an Export CSV link download as a spreadsheet.') }}
            </p>
        </div>

        @if (session('error'))
            <x-alert variant="danger">{{ session('error') }}</x-alert>
        @endif

        @foreach ($sections as [$heading, $blurb, $reports])
            @php
                $visible = array_filter($reports, fn (array $report): bool => Gate::allows($report[1]));
            @endphp

            @if ($visible)
                <section class="space-y-3">
                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-wider text-text-muted">{{ __($heading) }}</h2>
                        <p class="text-xs text-text-muted">{{ __($blurb) }}</p>
                    </div>

                    <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($visible as [$route, $ability, $title, $desc])
                            <li>
                                <a href="{{ route($route) }}" class="block h-full">
                                    <x-card class="h-full transition hover:bg-bg/60 dark:hover:bg-white/5">
                                        <p class="font-medium text-text-primary dark:text-white">{{ __($title) }}</p>
                                        <p class="mt-1 text-sm text-text-muted">{{ __($desc) }}</p>
                                    </x-card>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        @endforeach
    </div>
</x-app-layout>
