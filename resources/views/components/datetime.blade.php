@props(['value' => null, 'format' => 'M j, Y H:i', 'stacked' => true])

{{-- One instant, every configured US zone: the entry zone first, the others beneath it. --}}
@if ($value)
    @php
        $us = \App\Support\Timezones::toUs($value);
        $others = collect(\App\Support\Timezones::others());
    @endphp
    <span {{ $attributes->class(['inline-block leading-tight']) }}>
        <span class="text-text-primary dark:text-white">{{ $us->format($format) }}</span>
        <span class="text-[10px] font-semibold tracking-wide text-text-muted">{{ \App\Support\Timezones::usLabel() }}</span>
        @if ($stacked)<br>@endif
        @foreach ($others as $zone)
            <span class="text-xs text-text-muted">{{ \App\Support\Timezones::to($value, $zone['timezone'])->format($format) }} <span class="text-[10px] font-semibold tracking-wide">{{ $zone['label'] }}</span></span>@if (! $loop->last && ! $stacked), @elseif (! $loop->last)<br>@endif
        @endforeach
    </span>
@else
    <span class="text-text-muted">—</span>
@endif
