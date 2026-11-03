@extends('site.layout')
@php($biz = config('plans.business'))
@section('title', 'Contact us')
@section('content')
<div class="doc">
    <h1>Contact us</h1>
    <p>We are happy to help with orders, plans, payments and refunds. We reply within one working day.</p>
    <h2>Email</h2>
    <p><a href="mailto:{{ $biz['email'] }}">{{ $biz['email'] }}</a></p>
    @if($biz['phone'])
        <h2>Phone and WhatsApp</h2>
        <p>{{ $biz['phone'] }} (Monday to Saturday, 10 am to 7 pm)</p>
    @endif
    @if($biz['address'])
        <h2>Address</h2>
        <p>{{ $biz['legal_name'] }}<br>{{ $biz['address'] }}</p>
    @endif
    <h2>In the app</h2>
    <p>Open the Mcare+ or Mcare+ Partner app and go to Help to send us a message with your order details.</p>
</div>
@endsection
