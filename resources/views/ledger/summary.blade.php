@php
    /** @var string $kind company|dispatcher */
    $company = $kind === 'company';
    $balance = $position['balance'];
    $state = match (true) {
        $balance > 0 => ['tone' => 'text-status-cancelled', 'chip' => 'bg-red-100 text-red-800 dark:bg-red-500/20 dark:text-red-300',
            'title' => $company ? __('Company owes us') : __('We owe the dispatcher'),
            'hint' => $company ? __('Open commission not yet received.') : __('Earned commission not yet paid out.')],
        $balance < 0 => ['tone' => 'text-blue-600 dark:text-blue-300', 'chip' => 'bg-blue-100 text-blue-800 dark:bg-blue-500/20 dark:text-blue-300',
            'title' => $company ? __('Advance held') : __('Paid in advance'),
            'hint' => $company ? __('Paid ahead of work delivered. It is used up automatically by the next commission.') : __('Paid ahead of what was earned. It is used up by the next commission.')],
        default => ['tone' => 'text-status-paid', 'chip' => 'bg-green-100 text-green-800 dark:bg-green-500/20 dark:text-green-300',
            'title' => __('Fully settled'), 'hint' => __('Nothing owed either way.')],
    };
@endphp

<div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
    <x-card class="lg:col-span-2">
        <div class="flex items-center justify-between gap-3">
            <div class="text-xs uppercase tracking-wider text-text-muted">{{ $state['title'] }}</div>
            <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $state['chip'] }}">
                {{ $balance > 0 ? __('Outstanding') : ($balance < 0 ? __('Advance') : __('Settled')) }}
            </span>
        </div>
        <div class="mt-1 text-3xl font-semibold {{ $state['tone'] }}">${{ number_format(abs($balance), 2) }}</div>
        <p class="mt-1 text-xs text-text-muted">{{ $state['hint'] }}</p>
    </x-card>

    <x-card class="px-4 py-3">
        <div class="text-xs uppercase tracking-wider text-text-muted">{{ $company ? __('Total billed') : __('Total earned') }}</div>
        <div class="mt-1 text-xl font-semibold text-text-primary dark:text-white">${{ number_format($position['accrued'], 2) }}</div>
        <p class="mt-1 text-xs text-text-muted">{{ __('Before any reversals') }}</p>
    </x-card>

    <x-card class="px-4 py-3">
        <div class="text-xs uppercase tracking-wider text-text-muted">{{ $company ? __('Total received') : __('Total paid out') }}</div>
        <div class="mt-1 text-xl font-semibold text-text-primary dark:text-white">${{ number_format($position['settled'], 2) }}</div>
        <p class="mt-1 text-xs text-text-muted">{{ __('Including any advance') }}</p>
    </x-card>
</div>
