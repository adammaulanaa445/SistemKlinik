<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Polyclinic;
use App\Models\User;
use App\Models\Patient;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'     => 'Admin SistemKlinik',
            'email'    => 'admin@klinik.test',
            'password' => 'password123',
            'role'     => 'admin',
        ]);
        User::create([
            'name'     => 'Siti Petugas',
            'email'    => 'petugas@klinik.test',
            'password' => 'password123',
            'role'     => 'petugas',
        ]);
        User::create([
            'name'     => 'Farid Farmasi',
            'email'    => 'farmasi@klinik.test',
            'password' => 'password123',
            'role'     => 'farmasi',
        ]);

        $poliUmum = Polyclinic::create([
            'name'        => 'Poli Umum',
            'queue_code'  => 'A',
            'description' => 'Pelayanan kesehatan umum',
        ]);

        $dokterUser = User::create([
            'name'     => 'dr. Ahmad Maulana',
            'email'    => 'dokter@klinik.test',
            'password' => 'password123',
            'role'     => 'dokter',
        ]);

        Doctor::create([
            'user_id'        => $dokterUser->id,
            'polyclinic_id'  => $poliUmum->id,
            'doctor_code'    => 'DOK001',
            'specialization' => 'Dokter Umum',
            'phone'          => '081234567891',
        ]);

        $pasienUser = User::create([
            'name'     => 'Budi Santoso',
            'email'    => 'budi@example.com',
            'password' => 'password123',
            'role'     => 'pasien',
        ]);

        Patient::create([
            'user_id'    => $pasienUser->id,
            'nik'        => '3578012345678901',
            'gender'     => 'L',
            'birth_date' => '1990-05-10',
            'phone'      => '081234567890',
            'address'    => 'Jl. Merdeka No. 10, Surabaya',
        ]);
    }
}
