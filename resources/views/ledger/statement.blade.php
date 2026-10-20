@php
    /** @var string $kind company|dispatcher */
    /** @var string $action  filter form target */
    $company = $kind === 'company';
    $tone = fn (\App\Enums\LedgerEntryType $type): string => match ($type) {
        \App\Enums\LedgerEntryType::CommissionAccrued => 'bg-slate-100 text-slate-700 dark:bg-white/10 dark:text-gray-200',
        \App\Enums\LedgerEntryType::CommissionReversed => 'bg-red-100 text-red-800 dark:bg-red-500/20 dark:text-red-300',
        \App\Enums\LedgerEntryType::PaymentReceived, \App\Enums\LedgerEntryType::PayoutMade => 'bg-green-100 text-green-800 dark:bg-green-500/20 dark:text-green-300',
        \App\Enums\LedgerEntryType::PaymentReversed => 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300',
        default => 'bg-purple-100 text-purple-800 dark:bg-purple-500/20 dark:text-purple-300',
    };
    $money = function (float $value): string {
        return ($value < 0 ? '−' : '').'$'.number_format(abs($value), 2);
    };
@endphp

<x-card :padded="false">
    <div class="flex flex-wrap items-end justify-between gap-3 border-b border-border/60 px-4 py-3 dark:border-white/10">
        <div>
            <h3 class="text-sm font-semibold text-text-primary dark:text-white">{{ __('Statement') }}</h3>
            <p class="text-xs text-text-muted">
                {{ __('Every entry is permanent. A correction appears as a new line, never as an edit, so you can always see what happened and when.') }}
            </p>
        </div>
        <form method="GET" action="{{ $action }}" class="flex flex-wrap items-end gap-2">
            <div>
                <x-input-label for="from" :value="__('From')" />
                <x-text-input id="from" name="from" type="date" class="mt-1 block w-40" :value="request('from')" />
            </div>
            <div>
                <x-input-label for="to" :value="__('To')" />
                <x-text-input id="to" name="to" type="date" class="mt-1 block w-40" :value="request('to')" />
            </div>
            <x-button variant="secondary" type="submit">{{ __('Filter') }}</x-button>
            @if (request()->hasAny(['from', 'to']))
                <a href="{{ $action }}" class="pb-2 text-sm text-text-muted hover:underline">{{ __('Reset') }}</a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
            <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                <tr>
                    <th class="px-4 py-3">{{ __('Date') }}</th>
                    <th class="px-4 py-3">{{ __('Entry') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Debit') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Credit') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Balance') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border/60 dark:divide-white/5">
                @if (request()->filled('from'))
                    <tr class="bg-bg/60 text-text-muted dark:bg-white/5">
                        <td class="px-4 py-2" colspan="4">{{ __('Opening balance') }}</td>
                        <td class="px-4 py-2 text-right font-medium">{{ $money($statement['opening']) }}</td>
                    </tr>
                @endif

                @forelse ($statement['entries'] as $entry)
                    @php $debit = $entry->column() === 'debit'; @endphp
                    <tr class="align-top hover:bg-bg/60 dark:hover:bg-white/5">
                        <td class="px-4 py-3 whitespace-nowrap">
                            {{ $entry->occurred_on->format(df()) }}
                            <span class="mt-0.5 block text-xs text-text-muted">
                                <x-datetime :value="$entry->created_at" format="H:i" :stacked="false" />
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $tone($entry->entry_type) }}">
                                {{ $entry->entry_type->label() }}
                            </span>
                            <div class="mt-1 text-text-primary dark:text-white">{{ $entry->description }}</div>
                            <div class="text-xs text-text-muted">
                                {{ $entry->creator ? __('by :name', ['name' => $entry->creator->name]) : __('automatic') }}
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">{{ $debit ? '$'.number_format(abs((float) $entry->amount), 2) : '' }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">{{ $debit ? '' : '$'.number_format(abs((float) $entry->amount), 2) }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap font-semibold">
                            @if ((float) $entry->balance_after < 0)
                                <span class="text-blue-600 dark:text-blue-300">${{ number_format(abs((float) $entry->balance_after), 2) }}</span>
                                <span class="ml-1 rounded-full bg-blue-100 px-1.5 py-0.5 text-[10px] font-semibold text-blue-800 dark:bg-blue-500/20 dark:text-blue-300">{{ __('ADVANCE') }}</span>
                            @else
                                ${{ number_format((float) $entry->balance_after, 2) }}
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-text-muted">{{ __('No entries in this period.') }}</td>
                    </tr>
                @endforelse
            </tbody>
            @if ($statement['entries']->isNotEmpty())
                <tfoot>
                    <tr class="border-t border-border/60 bg-bg/60 font-semibold dark:border-white/10 dark:bg-white/5">
                        <td class="px-4 py-3" colspan="4">{{ __('Closing balance') }}</td>
                        <td class="px-4 py-3 text-right">{{ $money($statement['closing']) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    <p class="border-t border-border/60 px-4 py-3 text-xs text-text-muted dark:border-white/10">
        {{ $company
            ? __('Debit = commission billed to the company. Credit = money received from it. A negative balance is an advance.')
            : __('Credit = commission earned by the dispatcher. Debit = money paid out to them. A negative balance is an advance.') }}
    </p>
</x-card>
