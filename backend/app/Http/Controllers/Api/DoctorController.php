<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    // GET /api/doctors — daftar semua dokter (public)
    // Bisa difilter berdasarkan poli: /api/doctors?polyclinic_id=1
    public function index(Request $request)
    {
        $query = Doctor::with(['user', 'polyclinic']);

        if ($request->has('polyclinic_id')) {
            $query->where('polyclinic_id', $request->polyclinic_id);
        }

        $doctors = $query->get();

        return response()->json([
            'data' => $doctors,
        ]);
    }

    // GET /api/doctors/{id} — detail 1 dokter beserta jadwalnya (public)
    public function show($id)
    {
        $doctor = Doctor::with(['user', 'polyclinic', 'schedules'])->find($id);

        if (! $doctor) {
            return response()->json([
                'message' => 'Dokter tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'data' => $doctor,
        ]);
    }
}