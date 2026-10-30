@extends('adminlte::page')

@section('title','Users')

@section('content_header')
<h1 class="text-primary">Users</h1>
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
placeholder="Search by name or mobile"
value="{{ request('search') }}">
</div>

<div class="col-md-3">
<select name="status" class="form-control">
<option value="">All Status</option>
<option value="active">Active</option>
<option value="blocked">Blocked</option>
</select>
</div>

<div class="col-md-3">
<button class="btn btn-primary">
Search
</button>
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
<th width="250">Actions</th>
</tr>
</thead>

<tbody>

@foreach($users as $user)

<tr>

<td>{{ $user->id }}</td>

<td>{{ $user->name }}</td>

<td>{{ $user->mobile_number }}</td>

<td>

@if($user->status=='active')

<span class="badge badge-success">
Active
</span>

@else

<span class="badge badge-danger">
Blocked
</span>

@endif

</td>

<td>

<a href="/admin/users/{{ $user->id }}"
class="btn btn-sm btn-primary">
View
</a>

<a href="/admin/users/{{ $user->id }}/edit"
class="btn btn-sm btn-warning">
Edit
</a>

@if($user->status=='active')

<form action="/admin/users/{{ $user->id }}/block"
method="POST"
style="display:inline">
@csrf
<button class="btn btn-sm btn-danger">
Block
</button>
</form>

@else

<form action="/admin/users/{{ $user->id }}/unblock"
method="POST"
style="display:inline">
@csrf
<button class="btn btn-sm btn-success">
Activate
</button>
</form>

@endif

</td>

</tr>

@endforeach

</tbody>

</table>

{{ $users->links() }}

</div>

</div>

@stop