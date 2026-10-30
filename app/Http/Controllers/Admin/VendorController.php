<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Pharmacy;
use App\Models\Order;
use App\Models\PrescriptionPharmacy;
use Illuminate\Http\Request;

class VendorController extends Controller
{

    public function index()
    {
        $query = User::with('pharmacy')
    ->where('role','vendor');

        if(request('search')){
            $query->where('name','LIKE','%'.request('search').'%')
            ->orWhere('mobile_number','LIKE','%'.request('search').'%');
        }

        if(request('status')){
            $query->where('status',request('status'));
        }

        $vendors = $query->latest()->paginate(10);

        return view('admin.vendors.index',compact('vendors'));
    }


    public function show($id)
    {
        $vendor = User::where('role','vendor')->findOrFail($id);

        $pharmacy = Pharmacy::where('user_id',$id)->first();

        $acceptedOrders = 0;
        $rejectedOrders = 0;

        if($pharmacy){
            $acceptedOrders = Order::where('pharmacy_id',$pharmacy->id)
                ->where('status','accepted')
                ->count();

            $rejectedOrders = Order::where('pharmacy_id',$pharmacy->id)
                ->where('status','rejected')
                ->count();
        }

        return view('admin.vendors.show', compact(
            'vendor',
            'pharmacy',
            'acceptedOrders',
            'rejectedOrders'
        ));
    }


    public function pendingPharmacies()
    {
        $pharmacies = Pharmacy::where('verification_status','pending')
        ->latest()
        ->paginate(10);

        return view('admin.pharmacies.pending',compact('pharmacies'));
    }


    public function approvePharmacy($id)
    {
        $pharmacy = Pharmacy::findOrFail($id);

        $pharmacy->update([
            'verification_status'=>'approved',
            'is_verified'=>1
        ]);

        return back()->with('success','Pharmacy approved successfully');
    }


    // public function rejectPharmacy($id)
    // {
    //     $pharmacy = Pharmacy::findOrFail($id);

    //     $pharmacy->update([
    //         'verification_status'=>'rejected',
    //         'is_verified'=>0
    //     ]);

    //     return back()->with('success','Pharmacy rejected');
    // }


    public function block($id)
    {
        $vendor = User::where('role','vendor')->findOrFail($id);

        $vendor->update([
            'status'=>'blocked'
        ]);

        return back()->with('success','Vendor blocked');
    }


    public function unblock($id)
    {
        $vendor = User::where('role','vendor')->findOrFail($id);

        $vendor->update([
            'status'=>'active'
        ]);

        return back()->with('success','Vendor activated');
    }


    public function orders($id)
    {
        $pharmacy = Pharmacy::where('user_id',$id)->firstOrFail();

        $orders = Order::where('pharmacy_id',$pharmacy->id)
        ->latest()
        ->paginate(10);

        return view('admin.vendors.orders',compact('orders','pharmacy'));
    }


    public function prescriptions($id)
    {
        $pharmacy = Pharmacy::where('user_id',$id)->firstOrFail();

        $prescriptions = PrescriptionPharmacy::where('pharmacy_id',$pharmacy->id)
        ->with('prescription')
        ->latest()
        ->paginate(10);

        return view('admin.vendors.prescriptions',compact('prescriptions','pharmacy'));
    }
    public function rejectPharmacy(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $pharmacy = Pharmacy::findOrFail($id);

        $pharmacy->update([
            'verification_status' => 'rejected',
            'is_verified' => 0,
            'rejection_reason' => $request->reason
        ]);

        return redirect()->back()->with('success','Pharmacy rejected with reason');
    }
    public function pharmacyHistory()
    {
        $pharmacies = Pharmacy::whereIn('verification_status',['approved','rejected'])
            ->latest()
            ->paginate(10);

        return view('admin.pharmacies.history', compact('pharmacies'));
    }

    public function updatePharmacyStatus(Request $request, $id)
    {
        $request->validate([
            'verification_status' => 'required',
            'rejection_reason' => 'nullable|string|max:1000',
        ]);

        $pharmacy = Pharmacy::findOrFail($id);

        $data = [
            'verification_status' => $request->verification_status,
            'is_verified' => $request->verification_status === 'approved' ? 1 : 0,
        ];

        // Save rejection reason
        if ($request->verification_status === 'rejected') {

            $data['rejection_reason'] =
                $request->rejection_reason;

        } else {

            $data['rejection_reason'] = null;
        }

        $pharmacy->update($data);

        return back()->with(
            'success',
            'Pharmacy status updated successfully'
        );
    }
}