@extends('adminlte::page')

@section('title','Vendor Orders')

@section('content_header')
<h1>Vendor Orders</h1>
@stop

@section('content')

<table class="table table-bordered">

<tr>
<th>ID</th>
<th>Status</th>
<th>Payment</th>
<th>Date</th>
</tr>

@foreach($orders as $order)

<tr>

<td>{{ $order->id }}</td>

<td>{{ $order->status }}</td>

<td>{{ $order->payment_status }}</td>

<td>{{ $order->created_at }}</td>

</tr>

@endforeach

</table>

{{ $orders->links() }}

@stop