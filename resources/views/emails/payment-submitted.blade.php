@extends('emails.layout')
@section('body')
<h2 style="margin:0 0 12px;">Payment to review</h2>
<p><strong>{{ $payment->tenant->name }}</strong> reported <strong>{{ $payment->money() }}</strong> via {{ $payment->methodLabel() }} for the {{ $payment->plan?->name }} plan ({{ $payment->billing_cycle }}).</p>
@if ($payment->reference)<p>Reference: {{ $payment->reference }}</p>@endif
<p style="margin:24px 0;"><a href="{{ route('super-admin.payments.index', ['status' => 'pending']) }}" style="background:#E8622C;color:#fff;padding:12px 22px;text-decoration:none;font-weight:700;">Review payment</a></p>
@endsection
