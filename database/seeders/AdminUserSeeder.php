<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'System Admin',
            'mobile_number' => '9999999999',
            'email' => 'admin@medicineapp.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'preferred_language' => 'en',
            'status' => 'active',
        ]);
    }
}
