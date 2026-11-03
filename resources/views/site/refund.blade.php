@extends('site.layout')
@php($biz = config('plans.business'))
@section('title', 'Refund and cancellation policy')
@section('content')
<div class="doc">
    <h1>Refund and cancellation policy</h1>
    <p class="muted">Last updated: {{ \Carbon\Carbon::parse('2026-11-03')->format('j F Y') }}</p>

    <h2>Plans (Mcare Family and Mcare Pharmacy Premium)</h2>
    <ul>
        <li>Plans are paid in advance for one month or one year. They do not renew on their own, so there is nothing to cancel: the plan simply ends on its end date unless you pay again.</li>
        <li>If you are not happy, ask for a refund within 7 days of paying and we will refund the full amount.</li>
        <li>After 7 days we do not refund the rest of the current period, but you keep the plan until its end date.</li>
        <li>If you were charged twice or charged by mistake, we refund the extra payment in full.</li>
        <li>Approved refunds go back to the original payment method within 5 to 7 working days.</li>
    </ul>

    <h2>Medicine orders</h2>
    <ul>
        <li>Medicines are sold and delivered by licensed partner pharmacies through {{ $biz['name'] }}. Today most orders are paid on delivery.</li>
        <li>You can cancel an order free of charge until the pharmacy marks it as out for delivery.</li>
        <li>If a medicine arrives damaged, expired or different from your prescription, tell us or the pharmacy within 48 hours of delivery. It will be replaced, or refunded if paid online.</li>
        <li>For safety, medicines in good condition cannot be returned once delivered.</li>
    </ul>

    <h2>How to ask for a refund</h2>
    <p>Email <a href="mailto:{{ $biz['email'] }}">{{ $biz['email'] }}</a>@if($biz['phone']) or call {{ $biz['phone'] }}@endif with your registered phone number and the payment or order details.</p>
</div>
@endsection
