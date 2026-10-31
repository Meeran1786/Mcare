@extends('adminlte::page')

@section('title','Vendor Details')

@section('content_header')
<h1 style="color:#4328B7; font-weight:600;">Vendor Profile</h1>
@stop

@section('content')

<div class="row">

    <!-- VENDOR INFO -->
    <div class="col-md-4">
        <div class="card shadow-lg border-0">

            <div class="card-header text-white"
                 style="background: linear-gradient(45deg,#4e73df,#6f42c1)">
                Vendor Info
            </div>

            <div class="card-body">

                <p><b>Name:</b> {{ $vendor->name }}</p>
                <p><b>Mobile:</b> {{ $vendor->mobile_number }}</p>

                <p><b>Status:</b>
                    @if($vendor->status=='active')
                        <span class="badge badge-success px-3 py-2">Active</span>
                    @else
                        <span class="badge badge-danger px-3 py-2">Blocked</span>
                    @endif
                </p>

                @if($pharmacy && $pharmacy->verification_status == 'approved')
                    <hr>

                    <a href="/admin/vendors/{{ $vendor->id }}/orders"
                       class="btn btn-info btn-sm w-100 mb-2">
                        View Orders
                    </a>

                    <a href="/admin/vendors/{{ $vendor->id }}/prescriptions"
                       class="btn btn-warning btn-sm w-100">
                        View Prescriptions
                    </a>
                @endif

            </div>
        </div>
    </div>


    <!-- PHARMACY INFO -->
    <div class="col-md-8">
        @if($pharmacy)

        <div class="card shadow-lg border-0">

            <div class="card-header text-white"
                 style="background: linear-gradient(45deg,#36b9cc,#1cc88a)">
                Pharmacy Details
            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-6">

                        <!-- PHOTO -->
                        @if($pharmacy->pharmacy_photo)
                            <img src="{{ asset('storage/'.$pharmacy->pharmacy_photo) }}"
                                 style="width:120px;height:120px;border-radius:10px;object-fit:cover;margin-bottom:10px;">
                        @endif

                        <p><b>Name:</b> {{ $pharmacy->pharmacy_name }}</p>

                        <!-- MERGED ADDRESS -->
                        <p><b>Address:</b>
                            {{ implode(', ', array_filter([
                                $pharmacy->address_line,
                                $pharmacy->area,
                                $pharmacy->landmark,
                                $pharmacy->city,
                                $pharmacy->state,
                                $pharmacy->pincode
                            ])) }}
                        </p>

                    </div>

                    <div class="col-md-6">

                        <!-- FIXED FIELDS -->
                        <p><b>License No:</b> {{ $pharmacy->license_number ?? 'N/A' }}</p>
                        <p><b>GST No:</b> {{ $pharmacy->gst_number ?? 'N/A' }}</p>

                        <p><b>Status:</b>
                            @if($pharmacy->verification_status=='approved')
                                <span class="badge badge-success px-3 py-2">Approved</span>
                            @elseif($pharmacy->verification_status=='pending')
                                <span class="badge badge-warning px-3 py-2">Pending</span>
                            @else
                                <span class="badge badge-danger px-3 py-2">Rejected</span>
                            @endif
                        </p>

                        @if($pharmacy)
                        <form
                            action="{{ route('admin.pharmacy.updateStatus', $pharmacy->id) }}"
                            method="POST"
                        >
                            @csrf

                            <div style="margin-top:10px;">

                                <select
                                    id="verificationStatus"
                                    name="verification_status"
                                    class="form-control form-control-sm mb-2"
                                    style="width:220px;"
                                >

                                    <option
                                        value="pending"
                                        {{ $pharmacy->verification_status == 'pending' ? 'selected' : '' }}
                                    >
                                        Pending
                                    </option>

                                    <option
                                        value="approved"
                                        {{ $pharmacy->verification_status == 'approved' ? 'selected' : '' }}
                                    >
                                        Approved
                                    </option>

                                    <option
                                        value="rejected"
                                        {{ $pharmacy->verification_status == 'rejected' ? 'selected' : '' }}
                                    >
                                        Rejected
                                    </option>

                                </select>

                                <!-- REJECTION REASON -->

                                <div
                                    id="rejectionReasonBox"
                                    style="
                                        display:
                                        {{ $pharmacy->verification_status == 'rejected' ? 'block' : 'none' }};
                                    "
                                >

                                    <textarea
                                        name="rejection_reason"
                                        class="form-control mb-2"
                                        rows="3"
                                        placeholder="Enter rejection reason..."
                                    >{{ $pharmacy->rejection_reason }}</textarea>

                                </div>

                                <button
                                    type="submit"
                                    class="btn btn-success btn-sm"
                                >
                                    Update
                                </button>

                            </div>

                        </form>

                        @endif

                    </div>

                </div>


                <!-- DOCUMENTS -->
                <hr>
                <h5>📄 Documents</h5>

                <div class="row text-center">

                    <!-- LICENSE -->
                    <div class="col-md-4">
                        <b>License</b><br>
                        @if($pharmacy->license_file)
                            <a href="{{ $pharmacy->documentUrl('license_file') }}"
                               target="_blank"
                               class="btn btn-outline-primary btn-sm mt-2">
                                View
                            </a>
                        @else
                            <p class="text-danger mt-2">Not Uploaded</p>
                        @endif
                    </div>

                    <!-- GST -->
                    <div class="col-md-4">
                        <b>GST Proof</b><br>
                        @if($pharmacy->gst_file)
                            <a href="{{ $pharmacy->documentUrl('gst_file') }}"
                               target="_blank"
                               class="btn btn-outline-primary btn-sm mt-2">
                                View
                            </a>
                        @else
                            <p class="text-danger mt-2">Not Uploaded</p>
                        @endif
                    </div>

                    <!-- OWNER ID -->
                    <div class="col-md-4">
                        <b>Owner ID</b><br>
                        @if($pharmacy->owner_id_file)
                            <a href="{{ $pharmacy->documentUrl('owner_id_file') }}"
                               target="_blank"
                               class="btn btn-outline-primary btn-sm mt-2">
                                View
                            </a>
                        @else
                            <p class="text-danger mt-2">Not Uploaded</p>
                        @endif
                    </div>

                </div>


                <!-- APPROVAL -->
                @if($pharmacy->verification_status=='pending')

                    <hr>

                    @if($pharmacy->license_file && $pharmacy->gst_file && $pharmacy->owner_id_file)

                        <form action="/admin/pharmacies/{{ $pharmacy->id }}/approve"
                              method="POST" style="display:inline">
                            @csrf
                            <button class="btn btn-success px-4">
                                Approve
                            </button>
                        </form>

                    @else
                        <div class="alert alert-danger mt-2">
                            ⚠ Cannot approve. Missing documents.
                        </div>
                    @endif


                    <!-- REJECT -->
                    <form action="/admin/pharmacies/{{ $pharmacy->id }}/reject"
                          method="POST" class="mt-3">
                        @csrf

                        <textarea name="reason"
                                  class="form-control mb-2"
                                  placeholder="Enter rejection reason"
                                  required></textarea>

                        <button class="btn btn-danger">
                            Reject
                        </button>
                    </form>

                @endif

            </div>
        </div>

        @endif
    </div>

</div>


<!-- GRAPH -->
@if($pharmacy && $pharmacy->verification_status=='approved')

<div class="card shadow-lg border-0 mt-4">

    <div class="card-header text-white"
         style="background: linear-gradient(45deg,#f6c23e,#e74a3b)">
        📊 Order Analytics
    </div>

    <div class="card-body">
        <canvas id="orderChart" height="100"></canvas>
    </div>

</div>

@endif

@stop


@section('js')

@if($pharmacy && $pharmacy->verification_status=='approved')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
var ctx = document.getElementById('orderChart').getContext('2d');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Accepted', 'Rejected'],
        datasets: [{
            label: 'Orders',
            data: [{{ $acceptedOrders }}, {{ $rejectedOrders }}],
            backgroundColor: ['#1cc88a', '#e74a3b']
        }]
    }
});
</script>

<script>

document
    .getElementById('verificationStatus')
    ?.addEventListener('change', function () {

        const reasonBox =
            document.getElementById('rejectionReasonBox');

        if (this.value === 'rejected') {

            reasonBox.style.display = 'block';

        } else {

            reasonBox.style.display = 'none';

        }

    });

</script>

@endif

@stop