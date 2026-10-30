@extends('adminlte::page')

@section('title','Edit User')

@section('content_header')
<h1>Edit User</h1>
@stop

@section('content')

<div class="card">

<div class="card-body">

<form method="POST" action="/admin/users/{{ $user->id }}/update">

@csrf

<div class="form-group">
<label>Name</label>
<input type="text"
name="name"
class="form-control"
value="{{ $user->name }}">
</div>

<div class="form-group">
<label>Mobile</label>
<input type="text"
name="mobile_number"
class="form-control"
value="{{ $user->mobile_number }}">
</div>

<div class="form-group">
<label>Language</label>
<input type="text"
name="preferred_language"
class="form-control"
value="{{ $user->preferred_language }}">
</div>

<button class="btn btn-primary">
Update
</button>

</form>

</div>

</div>

@stop