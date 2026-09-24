<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Polyclinic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::create([
            'name'     => 'Admin SistemKlinik',
            'email'    => 'admin@klinik.test',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
        ]);

        // Petugas
        User::create([
            'name'     => 'Siti Petugas',
            'email'    => 'petugas@klinik.test',
            'password' => Hash::make('password123'),
            'role'     => 'petugas',
        ]);

        // Farmasi
        User::create([
            'name'     => 'Farid Farmasi',
            'email'    => 'farmasi@klinik.test',
            'password' => Hash::make('password123'),
            'role'     => 'farmasi',
        ]);

        // Poli contoh
        $poliUmum = Polyclinic::create([
            'name'        => 'Poli Umum',
            'queue_code'  => 'A',
            'description' => 'Pelayanan kesehatan umum',
        ]);

        // Dokter (user + data dokter)
        $dokterUser = User::create([
            'name'     => 'dr. Ahmad Maulana',
            'email'    => 'dokter@klinik.test',
            'password' => Hash::make('password123'),
            'role'     => 'dokter',
        ]);

        Doctor::create([
            'user_id'        => $dokterUser->id,
            'polyclinic_id'  => $poliUmum->id,
            'doctor_code'    => 'DOK001',
            'specialization' => 'Dokter Umum',
            'phone'          => '081234567891',
        ]);
    }
}