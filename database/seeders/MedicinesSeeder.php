<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Medicine;

class MedicinesSeeder extends Seeder
{
    public function run(): void
    {
        $medicines = [
            ['name' => 'Paracetamol', 'generic_name' => 'Acetaminophen', 'brand' => 'Crocin', 'category' => 'Pain Relief'],
            ['name' => 'Azithromycin', 'generic_name' => 'Azithromycin', 'brand' => 'Azee', 'category' => 'Antibiotic'],
            ['name' => 'Cetirizine', 'generic_name' => 'Cetirizine', 'brand' => 'Cetzine', 'category' => 'Allergy'],
            ['name' => 'Pantoprazole', 'generic_name' => 'Pantoprazole', 'brand' => 'Pantocid', 'category' => 'Gastric'],
            ['name' => 'Metformin', 'generic_name' => 'Metformin', 'brand' => 'Glycomet', 'category' => 'Diabetes'],
        ];

        foreach ($medicines as $medicine) {
            Medicine::create($medicine);
        }
    }
}
