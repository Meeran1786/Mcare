<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Pharmacy;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        // Vendor 1
        $vendor1 = User::create([
            'name' => 'Apollo Pharmacy',
            'mobile_number' => '9000000001',
            'email' => 'apollo@pharmacy.com',
            'password' => Hash::make('password'),
            'role' => 'vendor',
            'preferred_language' => 'en',
            'status' => 'active',
        ]);

        Pharmacy::create([
            'user_id' => $vendor1->id,
            'pharmacy_name' => 'Apollo Pharmacy',
            'license_number' => 'LIC-AP-001',
            'gst_number' => 'GST-AP-001',
            'address_line' => 'Main Road',
            'area' => 'Ameerpet',
            'city' => 'Hyderabad',
            'state' => 'Telangana',
            'pincode' => '500016',
            'latitude' => 17.437462,
            'longitude' => 78.448288,
            'is_verified' => true,
        ]);

        // Vendor 2
        $vendor2 = User::create([
            'name' => 'MedPlus Pharmacy',
            'mobile_number' => '9000000002',
            'email' => 'medplus@pharmacy.com',
            'password' => Hash::make('password'),
            'role' => 'vendor',
            'preferred_language' => 'en',
            'status' => 'active',
        ]);

        Pharmacy::create([
            'user_id' => $vendor2->id,
            'pharmacy_name' => 'MedPlus Pharmacy',
            'license_number' => 'LIC-MP-002',
            'gst_number' => 'GST-MP-002',
            'address_line' => 'Station Road',
            'area' => 'Kukatpally',
            'city' => 'Hyderabad',
            'state' => 'Telangana',
            'pincode' => '500072',
            'latitude' => 17.494793,
            'longitude' => 78.399644,
            'is_verified' => true,
        ]);
    }
}
