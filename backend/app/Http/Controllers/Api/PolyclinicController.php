<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Polyclinic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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

        // POST /api/polyclinics — tambah poli (admin)
    public function store(Request $request)
    {
        if ($request->filled('queue_code')) {
            $request->merge(['queue_code' => strtoupper($request->queue_code)]);
        }

        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'queue_code'  => 'required|string|max:5|unique:polyclinics,queue_code',
            'icon'        => 'nullable|string|max:100',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $polyclinic = Polyclinic::create($validator->validated());

        return response()->json([
            'message' => 'Poli berhasil ditambahkan',
            'data'    => $polyclinic,
        ], 201);
    }

    // PUT /api/polyclinics/{id} — ubah poli (admin)
    public function update(Request $request, $id)
    {
        $polyclinic = Polyclinic::find($id);

        if (! $polyclinic) {
            return response()->json(['message' => 'Poli tidak ditemukan'], 404);
        }

        if ($request->filled('queue_code')) {
            $request->merge(['queue_code' => strtoupper($request->queue_code)]);
        }

        $validator = Validator::make($request->all(), [
            'name'        => 'sometimes|required|string|max:255',
            'queue_code'  => [
                'sometimes', 'required', 'string', 'max:5',
                Rule::unique('polyclinics', 'queue_code')->ignore($polyclinic->id),
            ],
            'icon'        => 'nullable|string|max:100',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $polyclinic->update($validator->validated());

        return response()->json([
            'message' => 'Poli berhasil diubah',
            'data'    => $polyclinic->fresh(),
        ]);
    }

    // DELETE /api/polyclinics/{id} — hapus poli (admin)
    public function destroy($id)
    {
        $polyclinic = Polyclinic::find($id);

        if (! $polyclinic) {
            return response()->json(['message' => 'Poli tidak ditemukan'], 404);
        }

        if ($polyclinic->doctors()->exists()) {
            return response()->json([
                'message' => 'Poli masih memiliki dokter. Pindahkan atau hapus dokternya terlebih dahulu',
            ], 422);
        }

        if ($polyclinic->visits()->exists()) {
            return response()->json([
                'message' => 'Poli sudah memiliki riwayat kunjungan, tidak bisa dihapus',
            ], 422);
        }

        $polyclinic->delete();

        return response()->json(['message' => 'Poli berhasil dihapus']);
    }
}