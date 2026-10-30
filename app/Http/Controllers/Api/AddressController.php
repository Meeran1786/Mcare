<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{

    // ADD ADDRESS
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:100',
            'phone'        => 'required|digits:10',
            'address_line' => 'required|string',
            'latitude'     => 'required|numeric|between:-90,90',
            'longitude'    => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        try {

            $userId = $request->user()->id;

            // check how many addresses exist
            $existingCount = Address::where('user_id', $userId)->count();

            // get default flag from request
            $isDefault = $request->is_default ?? 0;

            // ✅ if first address → force default
            if ($existingCount == 0) {
                $isDefault = 1;
            }

            // ✅ if setting new default → reset others
            if ($isDefault == 1) {
                Address::where('user_id', $userId)
                    ->update(['is_default' => 0]);
            }

            $address = Address::create([
                'user_id'      => $userId,
                'label'        => $request->label ?? 'Home',
                'name'         => $request->name,
                'phone'        => $request->phone,
                'address_line' => $request->address_line,
                'city'         => $request->city,
                'state'        => $request->state,
                'pincode'      => $request->pincode,
                'latitude'     => $request->latitude,
                'longitude'    => $request->longitude,
                'is_default'   => $isDefault
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Address added successfully',
                'data'    => $address
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to save address',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    // LIST USER ADDRESSES
    public function index(Request $request)
    {
        $addresses = Address::where('user_id',$request->user()->id)
            ->orderByDesc('is_default')
            ->latest()
            ->get();

        return response()->json([
            'success'=>true,
            'data'=>$addresses
        ]);
    }

    // UPDATE ADDRESS
    public function update(Request $request, $id)
    {
        $address = Address::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:100',
            'phone'        => 'required|digits:10',
            'address_line' => 'required|string',
            'latitude'     => 'required|numeric|between:-90,90',
            'longitude'    => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $isDefault = $request->is_default ?? $address->is_default;

        if ((int) $isDefault === 1) {
            Address::where('user_id', $request->user()->id)
                ->where('id', '!=', $address->id)
                ->update(['is_default' => 0]);
        }

        $address->update([
            'label'        => $request->label ?? $address->label,
            'name'         => $request->name,
            'phone'        => $request->phone,
            'address_line' => $request->address_line,
            'city'         => $request->city,
            'state'        => $request->state,
            'pincode'      => $request->pincode,
            'latitude'     => $request->latitude,
            'longitude'    => $request->longitude,
            'is_default'   => $isDefault
        ]);

        Prescription::where('address_id', $address->id)
            ->whereNotIn('status', ['completed', 'cancelled', 'delivered', 'rejected'])
            ->update([
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]);

        Order::where('address_id', $address->id)
            ->whereNotIn('status', ['completed', 'cancelled', 'delivered', 'rejected'])
            ->update([
                'delivery_lat' => $request->latitude,
                'delivery_lng' => $request->longitude,
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Address updated successfully',
            'data' => $address->fresh()
        ]);
    }


    // DELETE ADDRESS
    public function destroy(Request $request,$id)
    {
        $address = Address::where('user_id',$request->user()->id)
                          ->findOrFail($id);

        $wasDefault = (int) $address->is_default === 1;
        $address->delete();

        if ($wasDefault) {
            $nextAddress = Address::where('user_id', $request->user()->id)->latest()->first();
            if ($nextAddress) {
                $nextAddress->update(['is_default' => 1]);
            }
        }

        return response()->json([
            'success'=>true,
            'message'=>'Address deleted successfully'
        ]);
    }
    public function setDefault(Request $request, $id)
    {
        $userId = $request->user()->id;

        // find address belonging to this user
        $address = Address::where('user_id', $userId)
                        ->where('id', $id)
                        ->firstOrFail();

        // reset all addresses for this user
        Address::where('user_id', $userId)
            ->update([
                'is_default' => 0
            ]);

        // set selected address as default
        $address->update([
            'is_default' => 1
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Default address updated'
        ]);
    }
}
