<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    // GET /api/patients?search=budi — daftar pasien (admin/petugas)
    public function index(Request $request)
    {
        $query = Patient::with('user')->orderBy('id', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('nik', 'like', "%{$search}%");
        }

        return response()->json(['data' => $query->get()]);
    }

    // GET /api/patients/{id} — detail 1 pasien beserta jumlah kunjungannya (admin/petugas)
    public function show($id)
    {
        $patient = Patient::with('user')->withCount('visits')->find($id);

        if (! $patient) {
            return response()->json(['message' => 'Pasien tidak ditemukan'], 404);
        }

        return response()->json(['data' => $patient]);
    }
}