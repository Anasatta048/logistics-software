@extends('emails.layout')
@section('body')
@php($what = $trial ? 'free trial' : 'subscription')
@if ($daysLeft > 0)
<h2 style="margin:0 0 12px;">Your {{ $what }} ends in {{ $daysLeft }} {{ \Illuminate\Support\Str::plural('day', $daysLeft) }}</h2>
<p>Hello {{ $tenant->contact_name ?: $tenant->name }}, the {{ $what }} for <strong>{{ $tenant->name }}</strong> ends on <strong>{{ $tenant->endsAt()?->format(df()) }}</strong>. Renew before then so your team is never locked out.</p>
@else
<h2 style="margin:0 0 12px;">Your {{ $what }} has ended</h2>
<p>Hello {{ $tenant->contact_name ?: $tenant->name }}, the {{ $what }} for <strong>{{ $tenant->name }}</strong> ended on {{ $tenant->endsAt()?->format(df()) }}.
@unless ($trial)You still have access for {{ \App\Models\Tenant::GRACE_DAYS }} grace days; after that the workspace locks. @endunless
Your data is safe and returns as soon as the payment is confirmed.</p>
@endif
<p style="margin:24px 0;"><a href="{{ route('billing.index') }}" style="background:#E8622C;color:#fff;padding:12px 22px;text-decoration:none;font-weight:700;">Renew now</a></p>
<p style="font-size:13px;color:#6B7280;">Pay by bank transfer, Raast, JazzCash or Easypaisa and upload the slip. We confirm it within one business day.</p>
@endsection
