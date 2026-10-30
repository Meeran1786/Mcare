@extends('adminlte::page')

@section('title','Pharmacy History')

@section('content_header')
<h3>Pharmacy History</h3>
@stop

@section('content')

<div class="row">

@foreach($pharmacies as $pharmacy)

<div class="col-md-4">
<div class="card p-3">

<h5>{{ $pharmacy->pharmacy_name }}</h5>

<p>Status:
@if($pharmacy->verification_status == 'approved')
<span class="badge badge-success">Approved</span>
@else
<span class="badge badge-danger">Rejected</span>
@endif
</p>

@if($pharmacy->verification_status == 'rejected')
<p><b>Reason:</b> {{ $pharmacy->rejection_reason }}</p>
@endif

</div>
</div>

@endforeach

</div>

{{ $pharmacies->links() }}

@stop