@php
    $brand = config('invoice.brand_color', '#E8622C');
    $navy = config('invoice.navy_color', '#1B2A4A');
    $logo = \App\Support\BrandTheme::logoDataUri();
    $money = fn ($v) => '$'.number_format((float) $v, 2);
    $isDriver = $subject instanceof \App\Models\Driver;
    $carrier = $isDriver ? $subject->company : $subject;
    $from = ($filters['from'] ?? null) ? \Illuminate\Support\Carbon::parse($filters['from'])->format('d M Y') : null;
    $to = ($filters['to'] ?? null) ? \Illuminate\Support\Carbon::parse($filters['to'])->format('d M Y') : null;
    $period = $from && $to ? "$from to $to" : ($from ? "From $from" : ($to ? "Up to $to" : 'All dates'));
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Settlement statement: {{ $subject->name }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 9.5px; color: #1F2333; margin: 0; }
        table { border-collapse: collapse; width: 100%; }
        .page { padding: 0 38px 70px; }
        .head { background: {{ $navy }}; color: #fff; padding: 22px 38px 18px; border-bottom: 5px solid {{ $brand }}; }
        .head td { vertical-align: middle; }
        .word { font-size: 22px; font-weight: bold; letter-spacing: 3px; text-align: right; }
        .sub { text-align: right; color: #C9D2E6; font-size: 10px; margin-top: 4px; }
        .label { font-size: 7.5px; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; color: {{ $brand }}; margin-bottom: 4px; }
        .card { border: 1px solid #E5E7EB; padding: 10px 14px; }
        .lines { margin-top: 20px; }
        .lines th { background: {{ $navy }}; color: #fff; font-size: 7.5px; letter-spacing: .6px; text-transform: uppercase; text-align: left; padding: 7px 7px; }
        .lines td { padding: 7px; border-bottom: 1px solid #E5E7EB; vertical-align: top; }
        .lines tr.alt td { background: #FAFAFC; }
        .r { text-align: right; white-space: nowrap; }
        .muted { color: #6B7280; }
        .totals { width: 44%; margin-left: auto; margin-top: 16px; }
        .totals td { padding: 6px 10px; border-bottom: 1px solid #E5E7EB; }
        .totals .grand td { background: {{ $brand }}; color: #fff; font-size: 13px; font-weight: bold; border: 0; padding: 10px; }
        .sign td { padding-top: 46px; width: 50%; }
        .sign .line { border-top: 1px solid #9CA3AF; padding-top: 4px; margin-right: 30px; color: #6B7280; font-size: 8.5px; }
        .foot { position: fixed; bottom: 0; left: 0; right: 0; background: {{ $navy }}; color: #C9D2E6; padding: 11px 38px; font-size: 8px; border-top: 3px solid {{ $brand }}; }
    </style>
</head>
<body>
    <div class="head">
        <table><tr>
            <td style="width:55%">
                @if ($logo)<img src="{{ $logo }}" style="height:34px">@else<span style="font-size:17px;font-weight:bold">{{ config('invoice.company_name') }}</span>@endif
            </td>
            <td><div class="word">SETTLEMENT</div><div class="sub">Period: {{ $period }}</div></td>
        </tr></table>
    </div>

    <div class="page">
        <table style="margin-top:20px"><tr>
            <td style="width:50%;vertical-align:top;padding-right:8px">
                <div class="card">
                    <div class="label">{{ $isDriver ? 'Driver' : 'Carrier' }}</div>
                    <div style="font-size:13px;font-weight:bold">{{ $subject->name }}</div>
                    @if ($isDriver)
                        <div class="muted">{{ $subject->truck_number ? 'Truck '.$subject->truck_number : '' }}</div>
                        @if ($carrier)<div class="muted">{{ $carrier->name }}{{ $carrier->mc_number ? ' · '.$carrier->mc_number : '' }}</div>@endif
                    @else
                        <div class="muted">{{ $subject->mc_number }}</div>
                    @endif
                </div>
            </td>
            <td style="width:50%;vertical-align:top;padding-left:8px">
                <div class="card">
                    <div class="label">Statement</div>
                    <div>Issued {{ now()->format('d M Y') }}</div>
                    <div class="muted">{{ $withDelivered ? 'Delivered and paid loads' : 'Paid loads only' }} · {{ $totals['count'] }} load{{ $totals['count'] === 1 ? '' : 's' }} · {{ number_format($totals['miles']) }} miles</div>
                </div>
            </td>
        </tr></table>

        <table class="lines">
            <thead><tr><th>Load</th><th>Pickup</th><th>Route</th><th class="r">Miles</th><th class="r">Gross</th><th class="r">Commission</th><th class="r">Net</th></tr></thead>
            <tbody>
                @foreach ($rows as $load)
                    <tr class="{{ $loop->even ? 'alt' : '' }}">
                        <td><b>{{ $load->reference_number }}</b><div class="muted">{{ $load->broker?->company_name }}</div></td>
                        <td>{{ \App\Support\Timezones::toUs($load->pickup_at)?->format('d M Y') }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($load->origin_address, 34) }}<div class="muted">to {{ \Illuminate\Support\Str::limit($load->destination_address, 34) }}</div></td>
                        <td class="r">{{ number_format((float) $load->total_miles) }}</td>
                        <td class="r">{{ $money($load->rate) }}</td>
                        <td class="r muted">{{ $money($load->company_commission_amount) }}@if ((float) $load->company_commission_percentage > 0)<div>{{ rtrim(rtrim(number_format((float) $load->company_commission_percentage, 2), '0'), '.') }}%</div>@endif</td>
                        <td class="r"><b>{{ $money((float) $load->rate - (float) $load->company_commission_amount) }}</b></td>
                    </tr>
                @endforeach
                @if ($rows->isEmpty())<tr><td colspan="7" class="muted" style="text-align:center;padding:24px">No finished loads in this period.</td></tr>@endif
            </tbody>
        </table>

        <table class="totals">
            <tr><td>Gross freight</td><td class="r">{{ $money($totals['gross']) }}</td></tr>
            <tr><td>Less commission</td><td class="r">- {{ $money($totals['commission']) }}</td></tr>
            <tr class="grand"><td>Net to {{ $isDriver ? 'driver' : 'carrier' }}</td><td class="r">{{ $money($totals['net']) }}</td></tr>
        </table>

        <table class="sign"><tr>
            <td><div class="line">Prepared by</div></td>
            <td><div class="line">Received by</div></td>
        </tr></table>
    </div>

    <div class="foot">{{ config('invoice.company_name') }} · {{ config('invoice.email') }} · {{ config('invoice.phone') }}</div>
</body>
</html>
