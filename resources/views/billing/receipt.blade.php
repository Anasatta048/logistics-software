<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1F2333; }
    .head { background: #1B2A4A; color: #fff; padding: 18px 24px; }
    .head h1 { margin: 0; font-size: 20px; }
    .bar { height: 3px; background: #E8622C; }
    table { width: 100%; border-collapse: collapse; margin-top: 18px; }
    td, th { padding: 9px 10px; border-bottom: 1px solid #E5E7EB; text-align: left; }
    th { font-size: 10px; text-transform: uppercase; color: #6B7280; }
    .total { font-size: 18px; font-weight: bold; color: #E8622C; }
    .muted { color: #6B7280; }
</style>
</head>
<body>
<div class="head"><h1>{{ config('app.name') }}</h1><div>Subscription payment receipt</div></div>
<div class="bar"></div>
<p style="margin-top:18px;">
    <strong>Receipt no.</strong> {{ $payment->receipt_number }}<br>
    <strong>Date</strong> {{ $payment->reviewed_at?->format('d M Y') }}<br>
    <strong>Received from</strong> {{ $payment->tenant->name }}@if ($payment->tenant->email) ({{ $payment->tenant->email }})@endif
</p>
<table>
    <tr><th>Description</th><th>Period</th><th>Paid via</th><th style="text-align:right">Amount</th></tr>
    <tr>
        <td>{{ $payment->plan?->name }} plan, {{ $payment->billing_cycle }}</td>
        <td>{{ $payment->period_start?->format('d M Y') }} to {{ $payment->period_end?->format('d M Y') }}</td>
        <td>{{ $payment->methodLabel() }}@if ($payment->reference)<br><span class="muted">Ref {{ $payment->reference }}</span>@endif</td>
        <td style="text-align:right" class="total">{{ $payment->money() }}</td>
    </tr>
</table>
<p class="muted" style="margin-top:28px;">Issued by {{ $legal['company'] }}, {{ $legal['address'] }}. This receipt confirms payment for the subscription period shown above.</p>
</body>
</html>
