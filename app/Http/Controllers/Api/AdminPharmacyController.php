<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy;
use Illuminate\Http\Request;

class AdminPharmacyController extends Controller
{
    public function pending()
    {
        return response()->json([
            'success' => true,
            'status_code' => 200,
            'message' => 'Pending pharmacies fetched.',
            'data' => Pharmacy::where('verification_status', 'pending')->get()
        ]);
    }

    public function approve($id)
    {
        $pharmacy = Pharmacy::find($id);

        if (!$pharmacy) {
            return response()->json([
                'success' => false,
                'status_code' => 404,
                'message' => 'Pharmacy not found.'
            ], 404);
        }

        $pharmacy->update([
            'verification_status' => 'approved',
            'is_verified' => true,
            'rejection_reason' => null
        ]);

        return response()->json([
            'success' => true,
            'status_code' => 200,
            'message' => 'Pharmacy approved successfully.'
        ]);
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:255'
        ]);

        $pharmacy = Pharmacy::find($id);

        if (!$pharmacy) {
            return response()->json([
                'success' => false,
                'status_code' => 404,
                'message' => 'Pharmacy not found.'
            ], 404);
        }

        $pharmacy->update([
            'verification_status' => 'rejected',
            'is_verified' => false,
            'rejection_reason' => $request->reason
        ]);

        return response()->json([
            'success' => true,
            'status_code' => 200,
            'message' => 'Pharmacy rejected successfully.'
        ]);
    }
}
