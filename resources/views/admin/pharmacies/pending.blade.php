@extends('adminlte::page')

@section('title','Pending Pharmacies')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1 style="color:#4328B7;font-weight:600;">
        Pending Pharmacy Verification
    </h1>
</div>
@stop


@section('content')

@if(session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif


<!-- PHARMACY CARDS -->
<div class="row">

@foreach($pharmacies as $pharmacy)

<div class="col-md-6 col-lg-4 mb-4">

<div class="card shadow-sm h-100" style="border-radius:14px;overflow:hidden;">

<!-- IMAGE -->
<div style="height:180px;">
    @if($pharmacy->pharmacy_photo)
    <img src="{{ asset('storage/'.$pharmacy->pharmacy_photo) }}"
         style="width:100%;height:100%;object-fit:cover;">
    @else
    <div class="d-flex align-items-center justify-content-center h-100 text-muted">
        No Image
    </div>
    @endif
</div>

<!-- BODY -->
<div class="card-body">

<h5 style="font-weight:600;">
    {{ $pharmacy->pharmacy_name }}
</h5>

<p class="text-muted mb-1">
    📍 {{ $pharmacy->city }}
</p>

<p class="mb-1">
    🪪 {{ $pharmacy->license_number }}
</p>

<span class="badge badge-warning">
    Pending
</span>

</div>

<!-- ACTIONS -->
<div class="card-footer bg-white border-0">

<div class="d-flex justify-content-between">

<button class="btn btn-outline-primary btn-sm"
        data-toggle="modal"
        data-target="#viewModal{{ $pharmacy->id }}">
    View
</button>

<form action="/admin/pharmacies/{{ $pharmacy->id }}/approve"
      method="POST">
    @csrf
    <button class="btn btn-success btn-sm">
        Approve
    </button>
</form>

<button class="btn btn-danger btn-sm"
        onclick="openRejectModal({{ $pharmacy->id }})">
    Reject
</button>

</div>

</div>

</div>

</div>


<!-- ================= FULL VIEW MODAL ================= -->
<div class="modal fade" id="viewModal{{ $pharmacy->id }}">
<div class="modal-dialog modal-lg">
<div class="modal-content" style="border-radius:16px;overflow:hidden;">

<!-- HEADER -->
<div style="background:linear-gradient(45deg,#4328B7,#6C63FF);color:white;padding:15px;">
    <h5>{{ $pharmacy->pharmacy_name }}</h5>
    <small>{{ $pharmacy->city }}, {{ $pharmacy->state }}</small>
</div>

<!-- BODY -->
<div class="modal-body">

<div class="row">

<div class="col-md-5">
    @if($pharmacy->pharmacy_photo)
    <img src="{{ asset('storage/'.$pharmacy->pharmacy_photo) }}"
         style="width:100%;border-radius:10px;">
    @endif
</div>

<div class="col-md-7">

<p><b>Owner:</b> {{ $pharmacy->owner_name }}</p>
<p><b>License:</b> {{ $pharmacy->license_number }}</p>
<p><b>GST:</b> {{ $pharmacy->gst_number }}</p>
<p><b>Registration:</b> {{ $pharmacy->registration_number }}</p>

<hr>

<p><b>Address:</b><br>
{{ $pharmacy->address_line }},
{{ $pharmacy->area }},
{{ $pharmacy->city }},
{{ $pharmacy->state }} - {{ $pharmacy->pincode }}
</p>

<hr>

<p><b>Timings:</b> {{ $pharmacy->opening_time }} - {{ $pharmacy->closing_time }}</p>

<p><b>Delivery:</b>
@if($pharmacy->is_delivery_available)
<span class="text-success">Available</span>
@else
<span class="text-danger">Not Available</span>
@endif
</p>

</div>

</div>

<hr>

<div class="row">

<div class="col-md-6">
    <label>License File</label>
    <img src="{{ $pharmacy->documentUrl('license_file') }}"
         style="width:100%;border-radius:8px;">
</div>

<div class="col-md-6">
    <label>GST File</label>
    <img src="{{ $pharmacy->documentUrl('gst_file') }}"
         style="width:100%;border-radius:8px;">
</div>

</div>

</div>

<!-- FOOTER -->
<div class="modal-footer">

<form action="/admin/pharmacies/{{ $pharmacy->id }}/approve" method="POST">
@csrf
<button class="btn btn-success">Approve</button>
</form>

<button class="btn btn-danger"
        onclick="openRejectModal({{ $pharmacy->id }})">
    Reject
</button>

</div>

</div>
</div>
</div>

@endforeach

</div>


<!-- REJECT MODAL -->
<div class="modal fade" id="rejectModal">
<div class="modal-dialog">
<div class="modal-content">

<form method="POST" id="rejectForm">
@csrf

<div class="modal-header">
<h5>Reject Pharmacy</h5>
</div>

<div class="modal-body">
<textarea name="reason"
          class="form-control"
          placeholder="Enter rejection reason..."
          required></textarea>
</div>

<div class="modal-footer">
<button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
<button class="btn btn-danger">Submit</button>
</div>

</form>

</div>
</div>
</div>


<!-- JS -->
<script>
function openRejectModal(id){
    document.getElementById('rejectForm').action =
        "/admin/pharmacies/" + id + "/reject";

    $('#rejectModal').modal('show');
}
</script>


<div class="d-flex justify-content-center">
    {{ $pharmacies->links() }}
</div>

@stop