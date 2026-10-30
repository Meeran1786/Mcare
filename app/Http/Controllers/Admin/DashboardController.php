<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Pharmacy;
use App\Models\Order;
use App\Models\Prescription;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $range = request('range', 7);

        $dates = [];
        $ordersData = [];
        $usersData = [];
        $vendorsData = [];
        $revenueData = [];

        // 📊 LOOP FOR GRAPH DATA
        for ($i = $range - 1; $i >= 0; $i--) {

            $date = Carbon::now()->subDays($i);

            $dates[] = $date->format('d M');

            $ordersData[] = Order::whereDate('created_at', $date)->count();

            $usersData[] = User::where('role','user')
                ->whereDate('created_at', $date)->count();

            $vendorsData[] = User::where('role','vendor')
                ->whereDate('created_at', $date)->count();

            // 💰 Revenue (important)
            $revenueData[] = Order::whereDate('created_at', $date)
                ->sum('total_amount');
        }


        // 🔢 KPIs
        $totalUsers = User::where('role','user')->count();
        $totalVendors = User::where('role','vendor')->count();
        $totalOrders = Order::count();
        $totalRevenue = Order::sum('total_amount');

        $pendingPharmacies = Pharmacy::where('verification_status','pending')->count();

        // 📊 STATUS COUNTS
        $completed = Order::where('status','completed')->count();
        $pending = Order::where('status','pending')->count();
        $cancelled = Order::where('status','cancelled')->count();


        // 🏆 TOP PHARMACIES
        $topPharmacies = Pharmacy::withCount('orders')
            ->orderByDesc('orders_count')
            ->take(5)
            ->get();


        // 📋 RECENT ORDERS (ACTIVITY FEED)
        $recentOrders = Order::with('user')
            ->latest()
            ->take(5)
            ->get();


        // 📍 MAP DATA (PHARMACIES WITH LOCATION)
        $mapPharmacies = Pharmacy::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get(['pharmacy_name','latitude','longitude']);


        return view('admin.dashboard', [

            // graphs
            'range' => $range,
            'dates' => $dates,
            'ordersData' => $ordersData,
            'usersData' => $usersData,
            'vendorsData' => $vendorsData,
            'revenueData' => $revenueData,

            // kpis
            'totalUsers' => $totalUsers,
            'totalVendors' => $totalVendors,
            'totalOrders' => $totalOrders,
            'totalRevenue' => $totalRevenue,
            'pendingPharmacies' => $pendingPharmacies,

            // status
            'completed' => $completed,
            'pending' => $pending,
            'cancelled' => $cancelled,

            // extras
            'topPharmacies' => $topPharmacies,
            'recentOrders' => $recentOrders,
            'mapPharmacies' => $mapPharmacies
        ]);
    }
    public function exportOrders()
    {
        $orders = \App\Models\Order::latest()->get();

        $filename = "orders_" . now()->format('Ymd_His') . ".csv";

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
        ];

        $callback = function() use ($orders) {
            $file = fopen('php://output', 'w');

            // HEADER
            fputcsv($file, [
                'ID',
                'User ID',
                'Pharmacy ID',
                'Status',
                'Amount',
                'Date'
            ]);

            foreach ($orders as $o) {
                fputcsv($file, [
                    $o->id,
                    $o->user_id,
                    $o->pharmacy_id,
                    $o->status,
                    $o->total_amount,
                    $o->created_at
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportDashboard(Request $request)
    {
        $type = $request->input('export_type', 'all');
        if (! in_array($type, ['all', 'users', 'pharmacies'], true)) {
            $type = 'all';
        }

        $fromDate = $request->filled('from_date')
            ? Carbon::parse($request->input('from_date'))->startOfDay()
            : Carbon::now()->subDays(6)->startOfDay();
        $toDate = $request->filled('to_date')
            ? Carbon::parse($request->input('to_date'))->endOfDay()
            : Carbon::now()->endOfDay();

        if ($fromDate->greaterThan($toDate)) {
            [$fromDate, $toDate] = [$toDate->copy()->startOfDay(), $fromDate->copy()->endOfDay()];
        }

        $users = collect();
        $pharmacies = collect();

        if (in_array($type, ['all', 'users'], true)) {
            $users = User::with('address')
                ->where('role', 'user')
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->orderBy('created_at')
                ->get();
        }

        if (in_array($type, ['all', 'pharmacies'], true)) {
            $pharmacies = Pharmacy::with('user')
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->orderBy('created_at')
                ->get();
        }

        $filename = 'dashboard_export_' . $fromDate->format('Ymd') . '_to_' . $toDate->format('Ymd') . '.xls';

        return response($this->buildDashboardExportHtml($users, $pharmacies, $fromDate, $toDate, $type), 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function buildDashboardExportHtml($users, $pharmacies, Carbon $fromDate, Carbon $toDate, string $type): string
    {
        $html = '<html><head><meta charset="UTF-8"></head><body>';
        $html .= '<h2>Mcare Dashboard Export</h2>';
        $html .= '<p><strong>From:</strong> ' . e($fromDate->format('d M Y')) . ' <strong>To:</strong> ' . e($toDate->format('d M Y')) . '</p>';
        $html .= '<p><strong>Selection:</strong> ' . e(ucfirst($type)) . '</p>';

        if (in_array($type, ['all', 'users'], true)) {
            $html .= '<h3>Total Users: ' . $users->count() . '</h3>';
            $html .= '<table border="1">';
            $html .= '<tr><th>ID</th><th>Name</th><th>Mobile</th><th>Email</th><th>Status</th><th>Preferred Language</th><th>Default Address</th><th>City</th><th>State</th><th>Pincode</th><th>Created At</th></tr>';

            foreach ($users as $user) {
                $address = $user->address;
                $html .= '<tr>';
                $html .= '<td>' . e($user->id) . '</td>';
                $html .= '<td>' . e($user->name) . '</td>';
                $html .= '<td>' . e($user->mobile_number) . '</td>';
                $html .= '<td>' . e($user->email) . '</td>';
                $html .= '<td>' . e($user->status) . '</td>';
                $html .= '<td>' . e($user->preferred_language) . '</td>';
                $html .= '<td>' . e($address->address_line ?? '') . '</td>';
                $html .= '<td>' . e($address->city ?? '') . '</td>';
                $html .= '<td>' . e($address->state ?? '') . '</td>';
                $html .= '<td>' . e($address->pincode ?? '') . '</td>';
                $html .= '<td>' . e(optional($user->created_at)->format('d M Y h:i A')) . '</td>';
                $html .= '</tr>';
            }

            $html .= '</table><br>';
        }

        if (in_array($type, ['all', 'pharmacies'], true)) {
            $html .= '<h3>Total Partners / Pharmacies: ' . $pharmacies->count() . '</h3>';
            $html .= '<table border="1">';
            $html .= '<tr><th>ID</th><th>Vendor Name</th><th>Vendor Mobile</th><th>Vendor Email</th><th>Owner Name</th><th>Pharmacy Name</th><th>License Number</th><th>GST Number</th><th>Registration Number</th><th>Address</th><th>Area</th><th>City</th><th>State</th><th>Pincode</th><th>Verification Status</th><th>Delivery Available</th><th>Opening Time</th><th>Closing Time</th><th>Created At</th></tr>';

            foreach ($pharmacies as $pharmacy) {
                $html .= '<tr>';
                $html .= '<td>' . e($pharmacy->id) . '</td>';
                $html .= '<td>' . e($pharmacy->user->name ?? '') . '</td>';
                $html .= '<td>' . e($pharmacy->user->mobile_number ?? '') . '</td>';
                $html .= '<td>' . e($pharmacy->user->email ?? '') . '</td>';
                $html .= '<td>' . e($pharmacy->owner_name) . '</td>';
                $html .= '<td>' . e($pharmacy->pharmacy_name) . '</td>';
                $html .= '<td>' . e($pharmacy->license_number) . '</td>';
                $html .= '<td>' . e($pharmacy->gst_number) . '</td>';
                $html .= '<td>' . e($pharmacy->registration_number) . '</td>';
                $html .= '<td>' . e($pharmacy->address_line) . '</td>';
                $html .= '<td>' . e($pharmacy->area) . '</td>';
                $html .= '<td>' . e($pharmacy->city) . '</td>';
                $html .= '<td>' . e($pharmacy->state) . '</td>';
                $html .= '<td>' . e($pharmacy->pincode) . '</td>';
                $html .= '<td>' . e($pharmacy->verification_status) . '</td>';
                $html .= '<td>' . e($pharmacy->is_delivery_available ? 'Yes' : 'No') . '</td>';
                $html .= '<td>' . e($pharmacy->opening_time) . '</td>';
                $html .= '<td>' . e($pharmacy->closing_time) . '</td>';
                $html .= '<td>' . e(optional($pharmacy->created_at)->format('d M Y h:i A')) . '</td>';
                $html .= '</tr>';
            }

            $html .= '</table>';
        }

        $html .= '</body></html>';

        return $html;
    }
}
