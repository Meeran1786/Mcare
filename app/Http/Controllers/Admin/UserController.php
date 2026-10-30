<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class UserController extends Controller
{

    public function index()
    {
        $query = User::where('role','user');

        if(request('search')){
            $query->where(function($q){
                $q->where('name','LIKE','%'.request('search').'%')
                ->orWhere('mobile_number','LIKE','%'.request('search').'%');
            });
        }

        if(request('status')){
            $query->where('status',request('status'));
        }

        $users = $query->latest()->paginate(10);

        return view('admin.users.index',compact('users'));
    }


    public function show($id)
    {
        $user = User::findOrFail($id);

        // Orders count
        $totalOrders = \App\Models\Order::where('user_id',$id)->count();

        $completedOrders = \App\Models\Order::where('user_id',$id)
                            ->where('status','completed')->count();

        $cancelledOrders = \App\Models\Order::where('user_id',$id)
                            ->where('status','cancelled')->count();

        // Prescriptions count
        $totalPrescriptions = \App\Models\Prescription::where('user_id',$id)->count();

        return view('admin.users.show', compact(
            'user',
            'totalOrders',
            'completedOrders',
            'cancelledOrders',
            'totalPrescriptions'
        ));
    }


    public function block($id)
    {
        $user = User::findOrFail($id);

        $user->update([
            'status'=>'blocked'
        ]);

        return back()->with('success','User blocked successfully');
    }


    public function unblock($id)
    {
        $user = User::findOrFail($id);

        $user->update([
            'status'=>'active'
        ]);

        return back()->with('success','User activated successfully');
    }


    public function edit($id)
    {
        $user = User::findOrFail($id);

        return view('admin.users.edit',compact('user'));
    }


    public function update($id)
    {
        $user = User::findOrFail($id);

        $user->update([
            'name'=>request('name'),
            'mobile_number'=>request('mobile_number'),
            'preferred_language'=>request('preferred_language')
        ]);

        return redirect('/admin/users')->with('success','User updated');
    }

}