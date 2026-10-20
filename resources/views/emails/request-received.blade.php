@extends('emails.layout')
@section('body')
<h2 style="margin:0 0 12px;">Thanks, {{ $tenantRequest->contact_name }}!</h2>
<p>We received the request for <strong>{{ $tenantRequest->company_name }}</strong>. We will review it and get back to you by email, usually within one business day.</p>
<p style="font-size:13px;color:#6B7280;">Questions? Just reply to this email.</p>
@endsection
