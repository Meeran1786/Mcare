<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pharmacy;
use App\Models\Medicine;
use Illuminate\Support\Facades\DB;

class PharmacyMedicinesSeeder extends Seeder
{
    public function run(): void
    {
        $pharmacies = Pharmacy::all();
        $medicines  = Medicine::all();

        foreach ($pharmacies as $pharmacy) {
            foreach ($medicines as $medicine) {
                DB::table('pharmacy_medicines')->insert([
                    'pharmacy_id' => $pharmacy->id,
                    'medicine_id' => $medicine->id,
                    'price' => rand(20, 200),
                    'stock_quantity' => rand(10, 100),
                    'is_available' => true,
                ]);
            }
        }
    }
}
