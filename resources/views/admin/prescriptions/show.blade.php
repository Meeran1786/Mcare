@extends('adminlte::page')

@section('title','Prescription Details')

@section('content_header')
<h1>Prescription #{{ $prescription->id }}</h1>
@stop

@section('content')

<h4>User</h4>

<p>Name: {{ $prescription->user->name }}</p>

<hr>

<h4>Files</h4>

@foreach($prescription->files as $file)

<a href="{{ $file->url }}" target="_blank">
View File
</a>

<br>

@endforeach

<hr>

<h4>Assigned Pharmacies</h4>

<table class="table">

<tr>
<th>Pharmacy</th>
<th>Status</th>
<th>Reason</th>
</tr>

@foreach($prescription->pharmacies as $p)

<tr>

<td>{{ $p->pharmacy->pharmacy_name }}</td>

<td>{{ $p->status }}</td>

<td>{{ $p->rejection_reason }}</td>

</tr>

@endforeach

</table>

<hr>

@if($order)

<h4>Order Created</h4>

<p>Order ID: {{ $order->id }}</p>
<p>Status: {{ $order->status }}</p>
<p>Payment: {{ $order->payment_status }}</p>

@endif

@stop