<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $meta['title'] }}</title>
    <style>
        @page { margin: 28px 28px 46px 28px; }
        * { font-family: Helvetica, Arial, sans-serif; }
        body { font-size: 9px; color: #1F2333; margin: 0; }
        .band { background: #1B2A4A; color: #fff; padding: 14px 16px; border-bottom: 3px solid #E8622C; }
        .band table { width: 100%; border-collapse: collapse; }
        .band td { vertical-align: middle; }
        .logo { height: 34px; }
        .title { font-size: 15px; font-weight: bold; text-align: right; }
        .sub { font-size: 8.5px; color: #C9D2E6; text-align: right; margin-top: 3px; }
        .info { margin: 12px 0 10px; border-collapse: collapse; width: 100%; }
        .info td { padding: 3px 0; font-size: 9px; vertical-align: top; }
        .info .k { width: 70px; color: #6B7280; text-transform: uppercase; font-size: 7.5px; letter-spacing: .6px; padding-top: 4px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data thead th { background: #F1F3F8; color: #1B2A4A; font-size: 7.5px; text-transform: uppercase; letter-spacing: .4px; text-align: left; padding: 6px 6px; border-bottom: 2px solid #1B2A4A; }
        table.data tbody td { padding: 5px 6px; border-bottom: 1px solid #E5E7EB; vertical-align: top; }
        table.data tbody tr:nth-child(even) td { background: #FAFAFC; }
        .r, table.data thead th.r { text-align: right; white-space: nowrap; }
        .empty { text-align: center; color: #6B7280; padding: 24px; }
        .sum { margin-top: 14px; width: 45%; margin-left: auto; border-collapse: collapse; }
        .sum td { padding: 5px 8px; border-bottom: 1px solid #E5E7EB; }
        .sum td:last-child { text-align: right; font-weight: bold; }
        .sum tr:last-child td { border-top: 2px solid #1B2A4A; }
        .foot { position: fixed; bottom: -30px; left: 0; right: 0; font-size: 7.5px; color: #6B7280; border-top: 1px solid #E5E7EB; padding-top: 5px; }
        .foot table { width: 100%; }
        .pagenum:before { content: "Page " counter(page); }
    </style>
</head>
<body>
    <div class="band">
        <table>
            <tr>
                <td style="width: 45%">@if ($logo)<img class="logo" src="{{ $logo }}" alt="">@else<strong style="font-size:14px">{{ $meta['company'] }}</strong>@endif</td>
                <td>
                    <div class="title">{{ $meta['title'] }}</div>
                    <div class="sub">{{ $meta['period'] }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="info">
        <tr><td class="k">Period</td><td>{{ $meta['period'] }}</td><td class="k">Generated</td><td>{{ $meta['generated'] }}</td></tr>
        @if ($meta['filters'])
            <tr><td class="k">Filters</td><td colspan="3">{{ implode('  ·  ', $meta['filters']) }}</td></tr>
        @endif
        <tr><td class="k">Records</td><td colspan="3">{{ number_format(count($rows)) }}</td></tr>
    </table>

    @php
        $numeric = [];
        foreach ($headings as $i => $h) {
            $values = array_filter(array_column($rows, $i), fn ($v) => $v !== null && $v !== '');
            $numeric[$i] = $values !== [] && count(array_filter($values, fn ($v) => is_int($v) || is_float($v) || (is_string($v) && preg_match('/^-?\d+(\.\d+)?$/', $v) && ! str_starts_with($v, '0')))) === count($values)
                && ! preg_match('/#|number|reference|phone|truck|mc /i', $h);
        }
        $fmt = function ($v, bool $num) {
            if ($v === null || $v === '') return '—';
            if ($num && is_numeric($v)) {
                return str_contains((string) $v, '.') || is_float($v) ? number_format((float) $v, 2) : number_format((float) $v);
            }
            return is_bool($v) ? ($v ? 'Yes' : 'No') : (string) $v;
        };
    @endphp

    <table class="data">
        <thead>
            <tr>
                @foreach ($headings as $i => $h)
                    <th class="{{ $numeric[$i] ? 'r' : '' }}">{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $i => $cell)
                        <td class="{{ ($numeric[$i] ?? false) ? 'r' : '' }}">{{ $fmt($cell, $numeric[$i] ?? false) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td class="empty" colspan="{{ count($headings) }}">No records match these filters.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($summary)
        <table class="sum">
            @foreach ($summary as $label => $value)
                <tr><td>{{ $label }}</td><td>{{ is_numeric($value) ? number_format((float) $value, 2) : $value }}</td></tr>
            @endforeach
        </table>
    @endif

    <div class="foot">
        <table><tr><td>{{ $meta['company'] }} · {{ $meta['title'] }}</td><td style="text-align:right"><span class="pagenum"></span></td></tr></table>
    </div>
</body>
</html>
