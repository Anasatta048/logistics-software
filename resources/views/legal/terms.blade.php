@extends('legal.layout')
@section('content')
<p>These terms govern your use of {{ config('app.name') }} (the "Service"), operated by {{ $legal['company'] }}, {{ $legal['address'] }} ("we", "us"). By requesting access or using the Service you agree to them on behalf of your company.</p>

<h2>1. The Service</h2>
<p>{{ config('app.name') }} is dispatch and brokerage management software: loads, drivers, brokers, invoices, commissions and accounting records. We provide it as a hosted subscription. Features depend on the plan you choose.</p>

<h2>2. Accounts and access</h2>
<ul>
    <li>We create your workspace after reviewing your request. The person we set up is your administrator and decides who else on your team gets access.</li>
    <li>You are responsible for keeping passwords confidential and for everything done under your users' accounts.</li>
    <li>Plan limits (users, drivers, loads per month) apply to your workspace. Reaching a limit blocks adding more until you upgrade.</li>
</ul>

<h2>3. Subscription and payment</h2>
<ul>
    <li>Plans are billed monthly or yearly in advance, in the currency shown on the plan, by bank transfer, Raast, JazzCash or Easypaisa. A subscription period starts when we confirm your payment.</li>
    <li>Free trials, where offered, need no payment. When a trial or paid period ends, your workspace has a short grace period and is then locked until you renew. Your data is kept.</li>
    <li>We may change prices with at least 30 days' notice; a change applies from your next renewal.</li>
</ul>

<h2>4. Your data</h2>
<p>You own the data you enter. You give us permission to store and process it only to run the Service for you. You can export your data from the Import &amp; Export page while your subscription is active. After a cancelled workspace has been inactive for 90 days we may delete its data.</p>

<h2>5. Acceptable use</h2>
<p>Do not misuse the Service: no attempts to access other workspaces, no scanning or load testing without our written consent, no unlawful content, and no reselling access without an agreement with us.</p>

<h2>6. Availability and support</h2>
<p>We work to keep the Service available and back up data daily, but we do not guarantee uninterrupted operation. Planned maintenance is announced where practical. Support is by email at <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>.</p>

<h2>7. Suspension and termination</h2>
<p>You may stop using the Service at any time; see the <a href="{{ route('legal.show', 'refund-policy') }}">Refund Policy</a>. We may suspend a workspace for non-payment, breach of these terms or security risk, and will tell you why.</p>

<h2>8. Liability</h2>
<p>The Service supports your bookkeeping but does not replace professional accounting or tax advice; you remain responsible for the accuracy of your records and filings. To the extent permitted by law, our total liability for any claim is limited to the fees you paid in the 3 months before it arose, and we are not liable for indirect or consequential loss.</p>

<h2>9. Changes and governing law</h2>
<p>We may update these terms; material changes are notified by email or in the app. Continued use after the effective date means acceptance. These terms are governed by the laws of Pakistan, and the courts of Pakistan have jurisdiction.</p>

<h2>10. Contact</h2>
<p>{{ $legal['company'] }}, {{ $legal['address'] }}. <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a></p>
@endsection
