<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PatientController extends Controller
{

    /*
    ======================================
    GET USER PATIENTS
    ======================================
    */
    public function index(Request $request)
    {

        $user = $request->user();

        /*
        Ensure "My Self" patient exists
        */
        $selfPatient = Patient::where('user_id', $user->id)
                              ->where('is_self', 1)
                              ->first();

        if (!$selfPatient) {

            $selfPatient = Patient::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'is_self' => 1
            ]);

        }

        /*
        Get all patients
        */
        $patients = Patient::where('user_id', $user->id)
                            ->orderByDesc('is_self')
                            ->get();

        return response()->json([
            'success' => true,
            'data' => $patients
        ]);

    }


    /*
    ======================================
    ADD PATIENT
    ======================================
    */
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(),[
            'name'   => 'required|string|max:100',
            'age'    => 'nullable|integer',
            'gender' => 'nullable|in:male,female,other'
        ]);

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ],422);

        }

        $patient = Patient::create([
            'user_id' => $request->user()->id,
            'name'    => $request->name,
            'age'     => $request->age,
            'gender'  => $request->gender,
            'is_self' => 0
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Patient added successfully',
            'data'    => $patient
        ]);

    }
    /*
    ======================================
    SET DEFAULT PATIENT
    ======================================
    */
    public function setDefault(Request $request, $id)
    {

        $userId = $request->user()->id;

        $patient = Patient::where('user_id', $userId)
                        ->where('id', $id)
                        ->firstOrFail();

        /*
        reset previous default patient
        */

        Patient::where('user_id', $userId)
                ->update([
                    'is_default' => 0
                ]);

        /*
        set selected patient
        */

        $patient->update([
            'is_default' => 1
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Default patient updated'
        ]);

    }

}