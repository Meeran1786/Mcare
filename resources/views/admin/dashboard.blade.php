@extends('adminlte::page')

@section('title','Enterprise Dashboard')

@section('content_header')
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mcare-dashboard-hero">
    <div>
        <h2>Mcare Enterprise Dashboard</h2>
        <small class="text-muted">Real-time insights and analytics for your healthcare operations.</small>
    </div>

    <div class="d-flex flex-column flex-sm-row gap-2 mt-3 mt-lg-0 mcare-toolbar">
        <form method="GET">
            <select name="range" onchange="this.form.submit()" class="form-control">
                <option value="7" {{ $range==7?'selected':'' }}>7 Days</option>
                <option value="30" {{ $range==30?'selected':'' }}>30 Days</option>
            </select>
        </form>
    </div>
</div>
@stop

@section('content')
@php
    $formatCompactAmount = function ($amount) {
        $amount = (float) $amount;

        if ($amount >= 10000000) {
            return rtrim(rtrim(number_format($amount / 10000000, 1), '0'), '.') . 'Cr';
        }

        if ($amount >= 100000) {
            return rtrim(rtrim(number_format($amount / 100000, 1), '0'), '.') . 'L';
        }

        if ($amount >= 1000) {
            return rtrim(rtrim(number_format($amount / 1000, 1), '0'), '.') . 'K';
        }

        return number_format($amount, $amount == floor($amount) ? 0 : 2);
    };
@endphp

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.export.dashboard') }}" class="row align-items-end">
            <div class="col-md-3 mb-3 mb-md-0">
                <label class="form-label">From Date</label>
                <input type="date" name="from_date" class="form-control" value="{{ request('from_date', now()->subDays($range - 1)->toDateString()) }}">
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <label class="form-label">To Date</label>
                <input type="date" name="to_date" class="form-control" value="{{ request('to_date', now()->toDateString()) }}">
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <label class="form-label">Export Data</label>
                <select name="export_type" class="form-control">
                    <option value="all">Users and Pharmacies</option>
                    <option value="users">Users Only</option>
                    <option value="pharmacies">Pharmacies Only</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-success btn-block">
                    <i class="fas fa-file-excel"></i> Export Excel
                </button>
            </div>
        </form>
    </div>
</div>

<div id="mcareDashboardData"
    data-dates='@json($dates)'
    data-orders='@json($ordersData)'
    data-revenue='@json($revenueData)'
    data-status='@json([$completed, $pending, $cancelled])'>
</div>

<div class="row" id="mcareStatsSection">
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card mcare-stat-card mcare-gradient-orders">
            <div class="card-body">
                <span class="mcare-stat-icon"><i class="fas fa-shopping-cart"></i></span>
                <p class="mcare-stat-value">{{ $totalOrders }}</p>
                <p class="mcare-stat-label">Orders</p>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card mcare-stat-card mcare-gradient-users">
            <div class="card-body">
                <span class="mcare-stat-icon"><i class="fas fa-users"></i></span>
                <p class="mcare-stat-value">{{ $totalUsers }}</p>
                <p class="mcare-stat-label">Users</p>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card mcare-stat-card mcare-gradient-vendors">
            <div class="card-body">
                <span class="mcare-stat-icon"><i class="fas fa-store"></i></span>
                <p class="mcare-stat-value">{{ $totalVendors }}</p>
                <p class="mcare-stat-label">Vendors</p>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card mcare-stat-card mcare-gradient-revenue">
            <div class="card-body">
                <span class="mcare-stat-icon"><i class="fas fa-rupee-sign"></i></span>
                <p class="mcare-stat-value mcare-stat-value-revenue" title="Rs {{ number_format($totalRevenue, 2) }}">
                    Rs {{ $formatCompactAmount($totalRevenue) }}
                </p>
                <p class="mcare-stat-label">Revenue</p>
            </div>
        </div>
    </div>
</div>

<div class="row mt-2">
    <div class="col-lg-8 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <span class="mcare-panel-title"><i class="fas fa-chart-line"></i> Orders and Revenue</span>
            </div>
            <div class="card-body">
                <div class="mcare-chart-wrap mcare-chart-wrap-lg">
                    <canvas id="ordersRevenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <span class="mcare-panel-title"><i class="fas fa-chart-pie"></i> Order Status</span>
            </div>
            <div class="card-body">
                <div class="mcare-chart-wrap mcare-chart-wrap-sm">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <span class="mcare-panel-title"><i class="fas fa-map-marker-alt"></i> Pharmacy Locations</span>
            </div>
            <div class="card-body">
                <div id="map" style="height:300px;"></div>
            </div>
        </div>
    </div>

    <!-- <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <span class="mcare-panel-title"><i class="fas fa-bolt"></i> Recent Activity</span>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush mcare-activity-list" id="activityFeed">
                    @foreach($recentOrders as $o)
                    <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                        <span>Order #{{ $o->id }} - {{ $o->status }}</span>
                        <strong>Rs {{ number_format($o->total_amount, 2) }}</strong>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div> -->
</div>

<!-- <div class="card mt-2">
    <div class="card-header">
        <span class="mcare-panel-title"><i class="fas fa-trophy"></i> Top Pharmacies</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Orders</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topPharmacies as $p)
                    <tr>
                        <td>{{ $p->pharmacy_name }}</td>
                        <td>{{ $p->orders_count }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div> -->
@stop

@section('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<style>
    .mcare-stat-card .card-body {
        position: relative;
        overflow: hidden;
    }

    .mcare-stat-card .mcare-stat-value {
        max-width: calc(100% - 62px);
        line-height: 1.15;
        white-space: nowrap;
    }

    .mcare-stat-value-revenue {
        letter-spacing: 0;
    }
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
function getDashboardDataFromElement(element) {
    return {
        dates: JSON.parse(element.dataset.dates || '[]'),
        orders: JSON.parse(element.dataset.orders || '[]'),
        revenue: JSON.parse(element.dataset.revenue || '[]'),
        status: JSON.parse(element.dataset.status || '[0,0,0]')
    };
}

var dashboardDataElement = document.getElementById('mcareDashboardData');
var dashboardData = getDashboardDataFromElement(dashboardDataElement);

var ordersRevenueChart = new Chart(document.getElementById('ordersRevenueChart'), {
    type: 'line',
    data: {
        labels: dashboardData.dates,
        datasets: [
            {
                label: 'Orders',
                data: dashboardData.orders,
                borderColor: '#2E86FF',
                backgroundColor: 'rgba(46, 134, 255, 0.08)',
                tension: 0.35,
                fill: true
            },
            {
                label: 'Revenue',
                data: dashboardData.revenue,
                borderColor: '#00C9A7',
                backgroundColor: 'rgba(0, 201, 167, 0.05)',
                tension: 0.35,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    color: '#1A1A2E'
                }
            }
        },
        scales: {
            x: {
                ticks: { color: '#6C757D' },
                grid: { color: 'rgba(26, 26, 46, 0.06)' }
            },
            y: {
                ticks: { color: '#6C757D' },
                grid: { color: 'rgba(26, 26, 46, 0.06)' }
            }
        }
    }
});

var statusChart = new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Completed','Pending','Cancelled'],
        datasets: [{
            data: dashboardData.status,
            backgroundColor: ['#00C9A7','#2E86FF','#FF4D6D'],
            borderColor: '#FFFFFF',
            borderWidth: 4,
            hoverOffset: 10
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    color: '#1A1A2E',
                    usePointStyle: true,
                    pointStyle: 'circle',
                    padding: 18
                }
            }
        }
    }
});

var map = L.map('map').setView([17.3850, 78.4867], 10);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
}).addTo(map);

var pharmacies = {!! json_encode($mapPharmacies) !!};

pharmacies.forEach(function(p){
    if(p.latitude && p.longitude){
        L.marker([p.latitude, p.longitude])
            .addTo(map)
            .bindPopup(p.pharmacy_name);
    }
});

function refreshDashboardStats() {
    fetch(window.location.href, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(function(response) {
        return response.text();
    })
    .then(function(html) {
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');
        var newStats = doc.getElementById('mcareStatsSection');
        var currentStats = document.getElementById('mcareStatsSection');
        var newDashboardDataElement = doc.getElementById('mcareDashboardData');

        if (newStats && currentStats) {
            currentStats.innerHTML = newStats.innerHTML;
        }

        if (newDashboardDataElement) {
            var newDashboardData = getDashboardDataFromElement(newDashboardDataElement);

            ordersRevenueChart.data.labels = newDashboardData.dates;
            ordersRevenueChart.data.datasets[0].data = newDashboardData.orders;
            ordersRevenueChart.data.datasets[1].data = newDashboardData.revenue;
            ordersRevenueChart.update();

            statusChart.data.datasets[0].data = newDashboardData.status;
            statusChart.update();

            dashboardDataElement.dataset.dates = newDashboardDataElement.dataset.dates;
            dashboardDataElement.dataset.orders = newDashboardDataElement.dataset.orders;
            dashboardDataElement.dataset.revenue = newDashboardDataElement.dataset.revenue;
            dashboardDataElement.dataset.status = newDashboardDataElement.dataset.status;
        }
    })
    .catch(function(error) {
        console.error('Unable to refresh dashboard stats:', error);
    });
}

setInterval(refreshDashboardStats, 30000);
</script>
@stop
