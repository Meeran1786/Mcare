@extends('adminlte::page')

@section('title','Prescriptions')

@section('content_header')
<h1>Prescriptions</h1>
@stop

@section('content')

<table class="table table-bordered">

<tr>
<th>ID</th>
<th>User</th>
<th>Status</th>
<th>Date</th>
<th>Action</th>
</tr>

@foreach($prescriptions as $p)

<tr>

<td>{{ $p->id }}</td>
<td>{{ $p->user->name }}</td>
<td>{{ $p->status }}</td>
<td>{{ $p->created_at }}</td>

<td>
<a href="/admin/prescriptions/{{ $p->id }}" class="btn btn-primary btn-sm">
View
</a>
</td>

</tr>

@endforeach

</table>

{{ $prescriptions->links() }}

@stop