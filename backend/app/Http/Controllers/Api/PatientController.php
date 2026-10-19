<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['search' => 'nullable|string|max:100']);

        $query = Patient::with('user')->orderBy('id', 'desc');
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($qq) use ($search) {
                    $qq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
                })->orWhere('nik', 'like', "%{$search}%");
            });
        }
        return response()->json(['data' => $query->get()]);
    }

    public function show($id)
    {
        $patient = Patient::with('user')->withCount('visits')->find($id);
        if (! $patient) return response()->json(['message' => 'Pasien tidak ditemukan'], 404);
        return response()->json(['data' => $patient]);
    }

    public function update(Request $request, $id)
    {
        $patient = Patient::find($id);
        if (! $patient) return response()->json(['message' => 'Pasien tidak ditemukan'], 404);

        $user = $request->user();
        if ($user->role === 'pasien' && (int)$user->patient?->id !== (int)$patient->id) {
            return response()->json(['message' => 'Anda tidak berwenang mengubah data ini'], 403);
        }

        $validated = $request->validate([
            'phone'           => 'sometimes|required|string|max:20',
            'address'         => 'sometimes|required|string|max:1000',
            'insurance_type'  => 'sometimes|required|in:bpjs,umum',
            'bpjs_number'     => 'sometimes|nullable|string|max:30',
        ]);

        $patient->update($validated);

        return response()->json(['message' => 'Profil berhasil diperbarui', 'data' => $patient->load('user')]);
    }
}
