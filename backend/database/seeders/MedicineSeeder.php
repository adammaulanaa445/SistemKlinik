<?php

namespace Database\Seeders;

use App\Models\Medicine;
use Illuminate\Database\Seeder;

class MedicineSeeder extends Seeder
{
    public function run(): void
    {
        $medicines = [
            ['code' => 'OBT001', 'name' => 'Paracetamol 500mg', 'category' => 'Analgesik', 'unit' => 'tablet', 'stock' => 200, 'price' => 1500],
            ['code' => 'OBT002', 'name' => 'Amoxicillin 500mg', 'category' => 'Antibiotik', 'unit' => 'kapsul', 'stock' => 100, 'price' => 3000],
            ['code' => 'OBT003', 'name' => 'CTM 4mg', 'category' => 'Antihistamin', 'unit' => 'tablet', 'stock' => 150, 'price' => 500],
            ['code' => 'OBT004', 'name' => 'Vitamin C 500mg', 'category' => 'Vitamin', 'unit' => 'tablet', 'stock' => 300, 'price' => 1000],
        ];

        foreach ($medicines as $medicine) {
            Medicine::create($medicine + ['status' => 'aktif']);
        }
    }
}