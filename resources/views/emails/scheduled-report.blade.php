@extends('emails.layout')
@section('body')
<h2 style="margin:0 0 12px;">{{ $reportName }}</h2>
<p>Your scheduled report is attached ({{ $filename }}). It covers the filters it was saved with.</p>
<p style="font-size:13px;color:#6B7280;">To change or stop this email, open Reports and edit your saved reports.</p>
@endsection
