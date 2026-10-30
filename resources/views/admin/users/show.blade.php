@extends('adminlte::page')

@section('title','User Details')

@section('content_header')
<h1 style="color:#4e73df; font-weight:600;">User Profile</h1>
@stop

@section('content')

<div class="row">

    <!-- PROFILE CARD -->
    <div class="col-md-4">
        <div class="card shadow-lg border-0">

            <div class="card-header text-white"
                 style="background: linear-gradient(45deg,#4e73df,#1cc88a)">
                User Info
            </div>

            <div class="card-body">

                <p><b>Name:</b> {{ $user->name }}</p>
                <p><b>Mobile:</b> {{ $user->mobile_number }}</p>

                <p><b>Status:</b>
                    @if($user->status=='active')
                        <span class="badge badge-success px-3 py-2">Active</span>
                    @else
                        <span class="badge badge-danger px-3 py-2">Blocked</span>
                    @endif
                </p>

                <p><b>Language:</b> {{ $user->preferred_language }}</p>

            </div>
        </div>
    </div>


    <!-- STATS -->
    <div class="col-md-8">

        <div class="row">

            <div class="col-md-4">
                <div class="card text-center shadow">
                    <div class="card-body">
                        <h3>{{ $totalOrders }}</h3>
                        <p>Total Orders</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card text-center shadow">
                    <div class="card-body">
                        <h3>{{ $completedOrders }}</h3>
                        <p>Completed</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card text-center shadow">
                    <div class="card-body">
                        <h3>{{ $cancelledOrders }}</h3>
                        <p>Cancelled</p>
                    </div>
                </div>
            </div>

        </div>

        <div class="card shadow mt-3">
            <div class="card-body text-center">
                <h4>{{ $totalPrescriptions }}</h4>
                <p>Total Prescriptions</p>
            </div>
        </div>

    </div>

</div>


<!-- GRAPH -->
<div class="card shadow-lg mt-4">

    <div class="card-header text-white"
         style="background: linear-gradient(45deg,#f6c23e,#e74a3b)">
        📊 User Activity
    </div>

    <div class="card-body">
        <canvas id="userChart" height="100"></canvas>
    </div>

</div>

@stop


@section('js')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
var ctx = document.getElementById('userChart').getContext('2d');

new Chart(ctx, {
    type: 'pie',
    data: {
        labels: ['Completed', 'Cancelled'],
        datasets: [{
            data: [{{ $completedOrders }}, {{ $cancelledOrders }}],
            backgroundColor: ['#1cc88a', '#e74a3b']
        }]
    }
});
</script>

@stop