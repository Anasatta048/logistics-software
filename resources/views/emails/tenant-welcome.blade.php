@extends('emails.layout')
@section('body')
<h2 style="margin:0 0 12px;">Welcome, {{ $admin->name }}!</h2>
<p>Your workspace <strong>{{ $tenant->name }}</strong> is ready. Set your password to sign in as its administrator.</p>
<p style="margin:24px 0;"><a href="{{ $setupUrl }}" style="background:#E8622C;color:#fff;padding:12px 22px;text-decoration:none;font-weight:700;">Set my password</a></p>
@if ($tenant->trial_ends_at)
<p>Your free trial runs until <strong>{{ $tenant->trial_ends_at->format('d M Y') }}</strong>.</p>
@endif
<p style="font-size:13px;color:#6B7280;">This link expires in 60 minutes. If it expires, use “Forgot password” on the sign-in page. Your login email is {{ $admin->email }}.</p>
@endsection
