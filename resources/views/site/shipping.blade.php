@extends('site.layout')
@php($biz = config('plans.business'))
@section('title', 'Shipping and delivery policy')
@section('content')
<div class="doc">
    <h1>Shipping and delivery policy</h1>
    <p class="muted">Last updated: {{ \Carbon\Carbon::parse('2026-11-03')->format('j F Y') }}</p>

    <h2>Medicine orders</h2>
    <ul>
        <li>Orders are packed and delivered by a licensed partner pharmacy near your delivery address.</li>
        <li>Most orders arrive the same day or the next day. The pharmacy confirms the price and expected time in the app before preparing your order.</li>
        <li>Delivery charges, if any, are shown in the price quote before you approve it.</li>
        <li>Prescription medicines are delivered only against a valid prescription.</li>
        <li>You can follow every order's status in the Mcare+ app.</li>
    </ul>

    <h2>Plans</h2>
    <p>Mcare Family and Mcare Pharmacy Premium are online services. Nothing is shipped: the plan starts in your account as soon as the payment succeeds.</p>

    <h2>Questions</h2>
    <p>Write to <a href="mailto:{{ $biz['email'] }}">{{ $biz['email'] }}</a>@if($biz['phone']) or call {{ $biz['phone'] }}@endif.</p>
</div>
@endsection
