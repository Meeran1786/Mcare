@extends('adminlte::page')

@section('title','Vendors')

@section('content_header')
<h1 style="color:#4328B7;">Vendor Management</h1>
@stop

@section('content')

<div class="card">

<div class="card-header">

<form method="GET">

<div class="row">

<div class="col-md-4">
<input type="text"
name="search"
class="form-control"
placeholder="Search vendor">
</div>

<div class="col-md-3">

<select name="status" class="form-control">
<option value="">All Status</option>
<option value="active">Active</option>
<option value="blocked">Blocked</option>
</select>

</div>

<div class="col-md-2">
<button class="btn btn-primary">Search</button>
</div>

</div>

</form>

</div>


<div class="card-body">

<table class="table table-hover">

<thead>

<tr>
<th>ID</th>
<th>Name</th>
<th>Mobile</th>
<th>Status</th>
<th>Actions</th>
</tr>

</thead>

<tbody>

@foreach($vendors as $vendor)

<tr>

<td>{{ $vendor->id }}</td>

<td>{{ $vendor->pharmacy->owner_name ?? 'N/A' }}</td>

<td>{{ $vendor->mobile_number }}</td>

<td>

@if($vendor->status=='active')

<span class="badge badge-success">Active</span>

@else

<span class="badge badge-danger">Blocked</span>

@endif

</td>

<td>

<a href="/admin/vendors/{{ $vendor->id }}"
class="btn btn-primary btn-sm">
View
</a>

@if($vendor->status=='active')

<form action="/admin/vendors/{{ $vendor->id }}/block"
method="POST"
style="display:inline">
@csrf
<button class="btn btn-danger btn-sm">
Block
</button>
</form>

@else

<form action="/admin/vendors/{{ $vendor->id }}/unblock"
method="POST"
style="display:inline">
@csrf
<button class="btn btn-success btn-sm">
Activate
</button>
</form>

@endif

</td>

</tr>

@endforeach

</tbody>

</table>

{{ $vendors->links() }}

</div>

</div>

@stop