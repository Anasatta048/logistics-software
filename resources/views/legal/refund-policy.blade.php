@extends('legal.layout')
@section('content')
<p>We want you to be sure before you commit. This policy explains when you can get your money back.</p>

<h2>Free trial</h2>
<p>Most plans start with a free trial and need no payment. Cancel any time during the trial and you owe nothing.</p>

<h2>First payment</h2>
<p>If you pay for a plan and are not satisfied, tell us within <strong>{{ $legal['refund_days'] }} days</strong> of the payment being confirmed and we will refund it in full to the account it came from.</p>

<h2>After that</h2>
<ul>
    <li>Monthly plans: no refunds for a period already started; you can cancel and keep access until the paid date.</li>
    <li>Yearly plans: after the first {{ $legal['refund_days'] }} days we refund the unused full months, less any discount that applied for paying yearly.</li>
    <li>If we cannot provide the Service for a long period because of a fault on our side, we credit the affected days.</li>
</ul>

<h2>Not refundable</h2>
<p>Suspensions caused by breach of the <a href="{{ route('legal.show', 'terms') }}">Terms</a>, and payments made to the wrong account or sent without the correct workspace reference (we will help you trace them).</p>

<h2>How to ask</h2>
<p>Email <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a> with your workspace name and the payment receipt number. We reply within 2 business days and send approved refunds within 7 business days.</p>
@endsection
