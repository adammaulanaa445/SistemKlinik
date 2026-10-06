<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class MedicineController extends Controller
{
    public function index(Request $request)
    {
        $query = Medicine::where('status', 'aktif')->orderBy('name');
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
            });
        }
        return response()->json(['data' => $query->get()]);
    }

    public function show($id)
    {
        $medicine = Medicine::find($id);
        if (! $medicine) return response()->json(['message' => 'Obat tidak ditemukan'], 404);
        return response()->json(['data' => $medicine]);
    }

    public function updateStock(Request $request, $id)
    {
        $validator = Validator::make($request->all(), ['stock' => 'required|integer|min:0']);
        if ($validator->fails()) return response()->json(['message' => 'Validasi gagal','errors' => $validator->errors()], 422);
        $medicine = Medicine::find($id);
        if (! $medicine) return response()->json(['message' => 'Obat tidak ditemukan'], 404);
        $medicine->update(['stock' => $request->stock]);
        return response()->json(['message' => 'Stok obat diperbarui','data' => $medicine]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:20|unique:medicines,code',
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'unit' => 'required|string|max:20',
            'stock' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
        ]);
        if ($validator->fails()) return response()->json(['message' => 'Validasi gagal','errors' => $validator->errors()], 422);
        $medicine = Medicine::create($validator->validated() + ['status' => 'aktif']);
        return response()->json(['message' => 'Obat berhasil ditambahkan','data' => $medicine], 201);
    }

    public function update(Request $request, $id)
    {
        $medicine = Medicine::find($id);
        if (! $medicine) return response()->json(['message' => 'Obat tidak ditemukan'], 404);
        $validator = Validator::make($request->all(), [
            'code' => ['sometimes','required','string','max:20',Rule::unique('medicines','code')->ignore($medicine->id)],
            'name' => 'sometimes|required|string|max:255',
            'category' => 'nullable|string|max:100',
            'unit' => 'sometimes|required|string|max:20',
            'price' => 'sometimes|required|numeric|min:0',
        ]);
        if ($validator->fails()) return response()->json(['message' => 'Validasi gagal','errors' => $validator->errors()], 422);
        $medicine->update($validator->validated());
        return response()->json(['message' => 'Obat berhasil diubah','data' => $medicine->fresh()]);
    }

    public function destroy($id)
    {
        $medicine = Medicine::find($id);
        if (! $medicine) return response()->json(['message' => 'Obat tidak ditemukan'], 404);
        $medicine->update(['status' => 'nonaktif']);
        return response()->json(['message' => 'Obat berhasil dinonaktifkan']);
    }

    public function activate($id)
    {
        $medicine = Medicine::find($id);
        if (! $medicine) return response()->json(['message' => 'Obat tidak ditemukan'], 404);
        $medicine->update(['status' => 'aktif']);
        return response()->json(['message' => 'Obat berhasil diaktifkan kembali']);
    }
}
