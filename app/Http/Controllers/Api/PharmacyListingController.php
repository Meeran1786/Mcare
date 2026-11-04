<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy;
use Illuminate\Http\Request;

class PharmacyListingController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'radius' => 'nullable|numeric|min:1|max:100',
            'search' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:100',
            'include_out_of_range' => 'nullable|boolean',
        ]);

        $lat = $request->input('lat');
        $lng = $request->input('lng');
        $radius = (float) $request->input('radius', 5);
        $search = $request->input('search');
        $perPage = (int) $request->input('limit', 10);
        $hasCoordinates = $request->filled('lat') && $request->filled('lng');
        $shouldFilterByDistance = $hasCoordinates && !$search && !$request->boolean('include_out_of_range');

        $query = Pharmacy::query()
            ->where('is_verified', true)
            ->where('is_delivery_available', true);

        if ($search) {
            $query->where('pharmacy_name', 'LIKE', "%{$search}%");
        }

        if ($hasCoordinates) {
            $query->select(
                'id',
                'pharmacy_name',
                'address_line',
                'area',
                'city',
                'state',
                'latitude',
                'longitude',
                'pharmacy_photo',
                'created_at'
            )
            ->selectRaw("(6371 * acos(LEAST(1, GREATEST(-1,
                    cos(radians(?))
                    * cos(radians(latitude))
                    * cos(radians(longitude) - radians(?))
                    + sin(radians(?))
                    * sin(radians(latitude))
                )))) AS distance_km", [$lat, $lng, $lat])
            ->selectRaw("CASE WHEN (6371 * acos(LEAST(1, GREATEST(-1,
                    cos(radians(?))
                    * cos(radians(latitude))
                    * cos(radians(longitude) - radians(?))
                    + sin(radians(?))
                    * sin(radians(latitude))
                )))) <= (? + 0) THEN 1 ELSE 0 END AS is_deliverable", [$lat, $lng, $lat, $radius])
            ->orderBy('distance_km', 'asc');

            if ($shouldFilterByDistance) {
                $query->whereRaw("(6371 * acos(LEAST(1, GREATEST(-1,
                    cos(radians(?))
                    * cos(radians(latitude))
                    * cos(radians(longitude) - radians(?))
                    + sin(radians(?))
                    * sin(radians(latitude))
                )))) <= (? + 0)", [$lat, $lng, $lat, $radius]);
            }
        } else {
            $query->select(
                'id',
                'pharmacy_name',
                'address_line',
                'area',
                'city',
                'state',
                'latitude',
                'longitude',
                'pharmacy_photo',
                'created_at'
            )
            ->orderBy('created_at', 'desc');
        }

        $pharmacies = $query->paginate($perPage);

        // Cover and extra shop photos, so patients can see the shop.
        $pharmacies->getCollection()->load('files')->each(function ($pharmacy) {
            $pharmacy->setAttribute('photo_urls', $pharmacy->photoUrls());
            // Joined Mcare in the last 30 days: the app marks it "New".
            $pharmacy->setAttribute('is_new', $pharmacy->created_at && $pharmacy->created_at->gt(now()->subDays(30)));
            $pharmacy->unsetRelation('files');
        });

        return response()->json([
            'success' => true,
            'status_code' => 200,
            'message' => 'Pharmacies fetched successfully.',
            'data' => $pharmacies,
        ]);
    }
}
