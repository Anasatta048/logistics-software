@php
    /** @var string $kind company|dispatcher */
    $company = $kind === 'company';
    $items = $position['items'];
@endphp

<x-card :padded="false">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-border/60 px-4 py-3 dark:border-white/10">
        <div>
            <h3 class="text-sm font-semibold text-text-primary dark:text-white">{{ __('Load by load') }}</h3>
            <p class="text-xs text-text-muted">
                {{ $company ? __('Money received is matched to the oldest commission first.') : __('Payouts are matched to the oldest commission first.') }}
            </p>
        </div>
        @if ($position['advance'] > 0)
            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800 dark:bg-blue-500/20 dark:text-blue-300">
                {{ $company ? __('Advance held') : __('Paid in advance') }}: ${{ number_format($position['advance'], 2) }}
            </span>
        @endif
    </div>

    @if ($items->isEmpty())
        <p class="px-4 py-6 text-sm text-text-muted">{{ __('No commission has been booked yet.') }}</p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border/60 text-sm dark:divide-white/5">
                <thead class="text-left text-xs uppercase tracking-wider text-text-muted">
                    <tr>
                        <th class="px-4 py-3">{{ __('Load') }}</th>
                        <th class="px-4 py-3">{{ __('Booked') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Commission') }}</th>
                        <th class="px-4 py-3 text-right">{{ $company ? __('Received') : __('Paid') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Remaining') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border/60 dark:divide-white/5">
                    @foreach ($items as $item)
                        @php
                            $settled = $item->remaining <= 0.005;
                            $partly = ! $settled && $item->applied > 0.005;
                            $age = (int) \Illuminate\Support\Carbon::parse($item->occurred_on)->diffInDays(now());
                        @endphp
                        <tr class="hover:bg-bg/60 dark:hover:bg-white/5">
                            <td class="px-4 py-3 font-medium text-text-primary dark:text-white">{{ $item->label }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-text-muted">
                                {{ \Illuminate\Support\Carbon::parse($item->occurred_on)->format('M j, Y') }}
                                @unless ($settled)
                                    <span class="block text-xs">{{ trans_choice(':count day open|:count days open', $age) }}</span>
                                @endunless
                            </td>
                            <td class="px-4 py-3 text-right">${{ number_format($item->amount, 2) }}</td>
                            <td class="px-4 py-3 text-right text-status-paid">${{ number_format($item->applied, 2) }}</td>
                            <td class="px-4 py-3 text-right font-semibold {{ $settled ? 'text-text-muted' : 'text-text-primary dark:text-white' }}">
                                ${{ number_format($item->remaining, 2) }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold
                                    {{ $settled ? 'bg-green-100 text-green-800 dark:bg-green-500/20 dark:text-green-300'
                                        : ($partly ? 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300'
                                        : 'bg-red-100 text-red-800 dark:bg-red-500/20 dark:text-red-300') }}">
                                    {{ $settled ? __('Paid') : ($partly ? __('Partly paid') : __('Unpaid')) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-card>
