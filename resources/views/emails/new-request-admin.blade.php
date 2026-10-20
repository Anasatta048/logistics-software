@extends('emails.layout')
@section('body')
<h2 style="margin:0 0 12px;">New access request</h2>
<p><strong>{{ $tenantRequest->company_name }}</strong><br>
{{ $tenantRequest->contact_name }} · {{ $tenantRequest->email }}@if($tenantRequest->phone) · {{ $tenantRequest->phone }}@endif<br>
Fleet: {{ $tenantRequest->fleet_size ?: '—' }} · Plan: {{ $tenantRequest->plan?->name ?? '—' }}</p>
@if ($tenantRequest->message)<p style="border-left:3px solid #E8622C;padding-left:12px;">{{ $tenantRequest->message }}</p>@endif
<p style="margin-top:24px;"><a href="{{ route('super-admin.requests.show', $tenantRequest) }}" style="background:#1B2A4A;color:#fff;padding:10px 20px;text-decoration:none;font-weight:700;">Review request</a></p>
@endsection
