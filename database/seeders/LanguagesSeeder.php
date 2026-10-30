<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguagesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('languages')->insert([
            ['code' => 'en', 'name' => 'English', 'is_active' => true],
            ['code' => 'te', 'name' => 'Telugu',  'is_active' => true],
            ['code' => 'hi', 'name' => 'Hindi',   'is_active' => true],
            ['code' => 'ur', 'name' => 'Urdu',    'is_active' => true],
        ]);
    }
}
