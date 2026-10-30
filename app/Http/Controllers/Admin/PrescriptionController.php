<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Models\Order;

class PrescriptionController extends Controller
{

    /*
    |---------------------------------------
    | All Prescriptions
    |---------------------------------------
    */

    public function index()
    {
        $query = Prescription::with('user');

        // Search by user mobile or id
        if(request('search')){
            $query->where('id',request('search'))
            ->orWhereHas('user',function($q){
                $q->where('mobile_number','LIKE','%'.request('search').'%');
            });
        }

        // Filter by status
        if(request('status')){
            $query->where('status',request('status'));
        }

        $prescriptions = $query->latest()->paginate(15);

        return view('admin.prescriptions.index',compact('prescriptions'));
    }



    /*
    |---------------------------------------
    | Prescription Details
    |---------------------------------------
    */

    public function show($id)
    {

        $prescription = Prescription::with([
            'user',
            'files',
            'pharmacies.pharmacy'
        ])->findOrFail($id);


        $order = Order::where('prescription_id',$id)->first();


        return view('admin.prescriptions.show',compact(
            'prescription',
            'order'
        ));
    }

}