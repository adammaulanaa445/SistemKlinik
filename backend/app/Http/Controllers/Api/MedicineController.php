<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MedicineController extends Controller
{
    // GET /api/medicines?search=para — daftar obat aktif
    public function index(Request $request)
    {
        $query = Medicine::where('status', 'aktif')->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return response()->json(['data' => $query->get()]);
    }

    // PATCH /api/medicines/{id}/stock — set stok obat
    public function updateStock(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'stock' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $medicine = Medicine::find($id);

        if (! $medicine) {
            return response()->json(['message' => 'Obat tidak ditemukan'], 404);
        }

        $medicine->update(['stock' => $request->stock]);

        return response()->json([
            'message' => 'Stok obat diperbarui',
            'data'    => $medicine,
        ]);
    }
}