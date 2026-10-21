@extends('legal.layout')
@section('content')
<p>This policy explains what personal and business information {{ $legal['company'] }} ("we") collects through {{ config('app.name') }} and how we use it.</p>

<h2>Information we collect</h2>
<ul>
    <li><strong>Account details:</strong> names, work emails, phone numbers and roles of the people your company adds.</li>
    <li><strong>Business records you enter:</strong> loads, drivers, brokers, companies, invoices, payments, expenses and accounting entries, and documents you upload (rate confirmations, BOL, POD, payment slips).</li>
    <li><strong>Technical data:</strong> IP address, browser type and actions in the app (the activity log), used for security and support.</li>
    <li><strong>Payment evidence:</strong> the transfer slip and reference you send us when paying a subscription.</li>
</ul>

<h2>How we use it</h2>
<ul>
    <li>To run the Service for your workspace, confirm payments and send billing, security and renewal emails.</li>
    <li>To keep the Service safe, investigate problems and provide support.</li>
    <li>To improve the product, using aggregated figures rather than the contents of your records.</li>
</ul>
<p>We do not sell your data and we do not use your business records for advertising.</p>

<h2>Who can see your data</h2>
<p>Only users of your workspace, according to the roles you set. Our staff access a workspace only to provide support or fix a fault, and every such entry is logged. We use trusted providers for hosting, email delivery and maps; they process data on our instructions only.</p>

<h2>Storage and security</h2>
<p>Workspaces are isolated from one another, passwords are stored hashed, traffic is encrypted over HTTPS, and the database is backed up daily. No system is perfectly secure; tell us at once if you suspect misuse of an account.</p>

<h2>Retention</h2>
<p>We keep your data while your subscription is active. After a workspace is cancelled we keep it for 90 days so you can come back or export it, then delete it. Payment receipts may be kept longer where the law requires.</p>

<h2>Your choices</h2>
<p>Your administrator can correct or export your records. To ask for access, correction or deletion of personal data, email <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>.</p>

<h2>Changes</h2>
<p>We will post updates here and notify you of significant ones by email.</p>

<p>{{ $legal['company'] }}, {{ $legal['address'] }}.</p>
@endsection
