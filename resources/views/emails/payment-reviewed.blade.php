@extends('emails.layout')
@section('body')
@if ($payment->isApproved())
<h2 style="margin:0 0 12px;">Payment received, thank you</h2>
<p>We confirmed <strong>{{ $payment->money() }}</strong> for <strong>{{ $payment->tenant->name }}</strong> ({{ $payment->plan?->name }}, {{ $payment->billing_cycle }}).</p>
<p>Your subscription is active until <strong>{{ $payment->period_end?->format(df()) }}</strong>. Receipt number: <strong>{{ $payment->receipt_number }}</strong>. You can download the receipt from Billing inside your workspace.</p>
@else
<h2 style="margin:0 0 12px;">We could not confirm your payment</h2>
<p>The payment of <strong>{{ $payment->money() }}</strong> reported for <strong>{{ $payment->tenant->name }}</strong> was not confirmed.</p>
@if ($payment->review_note)<p>Reason: {{ $payment->review_note }}</p>@endif
<p>Please check the details and submit it again from Billing, or reply to this email for help.</p>
@endif
@endsection
