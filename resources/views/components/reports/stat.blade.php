@props(['label', 'value', 'money' => false, 'currency' => 'usd', 'tone' => null, 'hint' => null])

{{-- One headline figure above a report table. $tone: 'bad' | 'good' | null --}}
<x-card class="px-4 py-3">
    <div class="text-xs uppercase tracking-wider text-text-muted">{{ $label }}</div>
    <div @class([
        'text-xl font-semibold',
        'text-status-cancelled' => $tone === 'bad',
        'text-status-paid' => $tone === 'good',
        'text-text-primary dark:text-white' => ! in_array($tone, ['bad', 'good'], true),
    ])>
        {{ $money ? ($currency === 'pkr' ? cur_local().' ' : cur_foreign()).number_format((float) $value, 2) : $value }}
    </div>
    @if ($hint)
        <div class="text-xs text-text-muted">{{ $hint }}</div>
    @endif
</x-card>
