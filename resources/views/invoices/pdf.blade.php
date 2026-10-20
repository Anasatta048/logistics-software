@php
    $brand = config('invoice.brand_color', '#E8622C');
    $navy = '#1B2A4A';
    $company = $invoice->company;


    $money = fn ($value) => '$'.number_format((float) $value, 2);
    $pct = fn ($value) => rtrim(rtrim(number_format((float) $value, 2), '0'), '.').'%';
    $paid = $invoice->amountPaid();
    $balance = $invoice->balance();
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 10px; color: #1F2333; margin: 0; }
        table { border-collapse: collapse; width: 100%; }
        .page { padding: 0 38px 120px; }
        .head { background: {{ $navy }}; color: #fff; padding: 26px 38px 22px; border-bottom: 5px solid {{ $brand }}; }
        .head td { vertical-align: middle; }
        .word { font-size: 30px; font-weight: bold; letter-spacing: 6px; text-align: right; }
        .num { text-align: right; color: #C9D2E6; font-size: 11px; margin-top: 4px; }
        .co { font-size: 9px; color: #C9D2E6; margin-top: 6px; line-height: 1.5; }
        .label { font-size: 7.5px; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; color: {{ $brand }}; margin-bottom: 5px; }
        .meta td { padding: 0; vertical-align: top; }
        .card { border: 1px solid #E5E7EB; padding: 12px 14px; }
        .kv td { padding: 3px 0; }
        .kv td.k { color: #6B7280; width: 46%; }
        .kv td.v { font-weight: bold; text-align: right; }
        .lines { margin-top: 22px; }
        .lines th { background: {{ $navy }}; color: #fff; font-size: 8px; letter-spacing: .6px; text-transform: uppercase; text-align: left; padding: 8px 8px; }
        .lines td { padding: 9px 8px; border-bottom: 1px solid #E5E7EB; vertical-align: top; }
        .lines tr.alt td { background: #FAFAFC; }
        .r, .lines th.r { text-align: right; white-space: nowrap; }
        .lines th.c { text-align: center; }
        .c { text-align: center; }
        .muted { color: #6B7280; }
        .ref { font-size: 8px; color: #6B7280; margin-top: 2px; }
        .totals { width: 46%; margin-left: auto; margin-top: 16px; }
        .totals td { padding: 6px 10px; }
        .totals tr.line td { border-bottom: 1px solid #E5E7EB; }
        .totals .grand td { background: {{ $brand }}; color: #fff; font-size: 14px; font-weight: bold; padding: 11px 10px; }
        .stamp { display: inline-block; padding: 4px 10px; font-size: 9px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; border: 2px solid; }
        .foot { position: fixed; bottom: 0; left: 0; right: 0; background: {{ $navy }}; color: #C9D2E6; padding: 14px 38px; font-size: 8.5px; border-top: 4px solid {{ $brand }}; }
        .foot b { color: #fff; }
    </style>
</head>
<body>
    {{-- Letterhead --}}
    <div class="head">
        <table>
            <tr>
                <td style="width: 55%;">
                    <div style="font-size: 18px; font-weight: bold; letter-spacing: 1px;">{{ strtoupper(config('invoice.company_name')) }}</div>
                    <div class="co">
                        @if (config('invoice.address')){{ config('invoice.address') }}<br>@endif
                        {{ config('invoice.email') }}@if (config('invoice.phone')) · {{ config('invoice.phone') }}@endif
                        @if (config('invoice.tax_id'))<br>Tax ID: {{ config('invoice.tax_id') }}@endif
                    </div>
                </td>
                <td>
                    <div class="word">INVOICE</div>
                    <div class="num"># {{ $invoice->invoice_number }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="page">
        {{-- Bill to / invoice details --}}
        <table class="meta" style="margin-top: 22px;">
            <tr>
                <td style="width: 52%; padding-right: 16px;">
                    <div class="label">Bill to</div>
                    <div class="card" style="min-height: 104px;">
                        <div style="font-size: 13px; font-weight: bold;">{{ $company->name }}</div>
                        @if ($company->contact_person)<div style="margin-top: 3px;">{{ $company->contact_person }}</div>@endif
                        <div class="muted" style="margin-top: 3px;">MC # {{ $company->mc_number }}</div>
                        @if ($company->address)<div class="muted">{{ strtoupper($company->address) }}</div>@endif
                        @if ($company->phone)<div class="muted">{{ $company->phone }}</div>@endif
                        @if ($company->email)<div class="muted">{{ $company->email }}</div>@endif
                    </div>
                </td>
                <td style="width: 48%;">
                    <div class="label">Invoice details</div>
                    <div class="card" style="min-height: 104px;">
                        <table class="kv">
                            <tr><td class="k">Invoice date</td><td class="v">{{ $invoice->invoice_date->format('M j, Y') }}</td></tr>
                            <tr><td class="k">Due date</td><td class="v">{{ $invoice->due_date->format('M j, Y') }}</td></tr>
                            <tr><td class="k">Period</td><td class="v">{{ $invoice->period_from->format('M j') }} – {{ $invoice->period_to->format('M j, Y') }}</td></tr>
                            <tr><td class="k">Driver</td><td class="v">{{ strtoupper($invoice->driver_name) }}</td></tr>
                            @if ($invoice->dispatcher_name)
                                <tr><td class="k">Dispatcher</td><td class="v">{{ strtoupper($invoice->dispatcher_name) }} · {{ $invoice->dispatcher_phone ?: config('invoice.phone') }}</td></tr>
                            @endif
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        {{-- Lines --}}
        <table class="lines">
            <thead>
                <tr>
                    <th style="width: 12%;">Date</th>
                    <th>Load / route</th>
                    <th class="r" style="width: 10%;">RPM</th>
                    <th class="c" style="width: 8%;">%</th>
                    <th class="r" style="width: 15%;">Load rate</th>
                    <th class="r" style="width: 15%;">Fee</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->lines as $line)
                    <tr class="{{ $loop->even ? 'alt' : '' }}">
                        <td>{{ $line->load_date->format('M j, Y') }}</td>
                        <td>
                            <div style="font-weight: bold;">{{ $line->description }}</div>
                            @if ($line->loadRecord?->reference_number)<div class="ref">{{ $line->loadRecord->reference_number }}</div>@endif
                        </td>
                        <td class="r muted">{{ $line->ratePerMile() !== null ? '$'.number_format($line->ratePerMile(), 2) : '—' }}</td>
                        <td class="c muted">{{ $pct($line->percentage) }}</td>
                        <td class="r">{{ $money($line->rate) }}</td>
                        <td class="r" style="font-weight: bold;">{{ $money($line->fee) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        <table style="margin-top: 4px;">
            <tr>
                <td style="vertical-align: top; padding-top: 18px; width: 50%;">
                    @if ($invoice->notes)
                        <div class="label">Notes</div>
                        <div style="line-height: 1.5;">{{ $invoice->notes }}</div>
                    @endif
                    <div style="margin-top: 16px; font-style: italic; color: #6B7280;">Thank you for your business!</div>
                    @if ($balance <= 0 && $paid > 0)
                        <div class="stamp" style="margin-top: 12px; color: #2E7D32; border-color: #2E7D32;">Paid in full</div>
                    @elseif ($paid > 0)
                        <div class="stamp" style="margin-top: 12px; color: #C9972E; border-color: #C9972E;">Partially paid</div>
                    @endif
                </td>
                <td style="vertical-align: top;">
                    <table class="totals" style="width: 100%;">
                        <tr class="line"><td class="muted">Total weekly gross</td><td class="r">{{ $money($invoice->gross_total) }}</td></tr>
                        <tr class="line"><td class="muted">Subtotal (commission)</td><td class="r" style="font-weight: bold;">{{ $money($invoice->total) }}</td></tr>
                        @if ((float) $invoice->previous_balance > 0)
                            <tr class="line"><td class="muted">Previous balance</td><td class="r">+ {{ $money($invoice->previous_balance) }}</td></tr>
                        @endif
                        @if ((float) $invoice->credit_applied > 0)
                            <tr class="line"><td class="muted">Advance credit</td><td class="r">- {{ $money($invoice->credit_applied) }}</td></tr>
                        @endif
                        @if ($paid > 0)
                            <tr class="line"><td class="muted">Received</td><td class="r">- {{ $money($paid) }}</td></tr>
                        @endif
                        <tr class="grand"><td>{{ $paid > 0 ? 'Balance due' : 'Total due' }}</td><td class="r">{{ $money($paid > 0 ? $balance : $invoice->due()) }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    @if (config('invoice.note'))
        <div style="margin-top: 14px; font-size: 11px; color: #555; white-space: pre-line;">{{ config('invoice.note') }}</div>
    @endif

    <div class="foot">
        <table>
            <tr>
                <td>Questions about this invoice? <b>{{ config('invoice.email') }}</b> · <b>{{ config('invoice.phone') }}</b></td>
                <td style="text-align: right;"><b>{{ config('invoice.website') }}</b></td>
            </tr>
        </table>
    </div>
</body>
</html>
