@extends('emails.layout')
@section('body')
<h2 style="margin:0 0 12px;">Hello {{ $tenantRequest->contact_name }},</h2>
<p>Thank you for your interest in {{ config('app.name') }}. Unfortunately we are not able to set up a workspace for <strong>{{ $tenantRequest->company_name }}</strong> right now.</p>
<p>If you think this is a mistake, reply to this email and we will take another look.</p>
@endsection
