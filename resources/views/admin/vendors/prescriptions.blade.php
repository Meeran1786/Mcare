@extends('adminlte::page')

@section('title','Vendor Prescriptions')

@section('content_header')
<h1>Vendor Prescriptions</h1>
@stop

@section('content')

<table class="table table-bordered">

<tr>
<th>ID</th>
<th>Status</th>
<th>Date</th>
</tr>

@foreach($prescriptions as $p)

<tr>

<td>{{ $p->prescription_id }}</td>

<td>{{ $p->status }}</td>

<td>{{ $p->created_at }}</td>

</tr>

@endforeach

</table>

{{ $prescriptions->links() }}

@stop