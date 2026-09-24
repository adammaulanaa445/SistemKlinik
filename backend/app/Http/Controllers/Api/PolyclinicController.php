<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Polyclinic;
use Illuminate\Http\Request;

class PolyclinicController extends Controller
{
    // GET /api/polyclinics — daftar semua poli (public)
    public function index()
    {
        $polyclinics = Polyclinic::withCount('doctors')->get();

        return response()->json([
            'data' => $polyclinics,
        ]);
    }

    // GET /api/polyclinics/{id} — detail 1 poli beserta dokternya (public)
    public function show($id)
    {
        $polyclinic = Polyclinic::with('doctors.user')->find($id);

        if (! $polyclinic) {
            return response()->json([
                'message' => 'Poli tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'data' => $polyclinic,
        ]);
    }
}