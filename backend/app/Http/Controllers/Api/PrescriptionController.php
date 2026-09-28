<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrescriptionController extends Controller
{
    private array $relations = [
        'medicalRecord.visit.patient.user',
        'medicalRecord.visit.doctor.user',
        'items.medicine',
    ];

    // GET /api/prescriptions?status=menunggu — daftar resep masuk
    public function index(Request $request)
    {
        $query = Prescription::with($this->relations)->orderBy('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json(['data' => $query->get()]);
    }

    // GET /api/prescriptions/{id} — detail resep + pengecekan stok tiap obat
    public function show($id)
    {
        $prescription = Prescription::with($this->relations)->find($id);

        if (! $prescription) {
            return response()->json(['message' => 'Resep tidak ditemukan'], 404);
        }

        $shortages = $this->findShortages($prescription);

        return response()->json([
            'data'      => $prescription,
            'stock_ok'  => empty($shortages),
            'shortages' => $shortages,
        ]);
    }

    // PATCH /api/prescriptions/{id}/process — proses resep, stok berkurang
    public function process($id)
    {
        $prescription = Prescription::with('items.medicine')->find($id);

        if (! $prescription) {
            return response()->json(['message' => 'Resep tidak ditemukan'], 404);
        }

        if (! in_array($prescription->status, ['menunggu', 'tertunda'])) {
            return response()->json([
                'message' => 'Resep ini sudah diproses',
            ], 422);
        }

        $shortages = DB::transaction(function () use ($prescription) {
            // Jumlahkan kebutuhan per obat (jaga-jaga kalau obat yang sama muncul 2x)
            $needed = $prescription->items
                ->groupBy('medicine_id')
                ->map(fn ($group) => $group->sum('quantity'));

            // Kunci baris obat supaya stok tidak bentrok dengan proses lain
            $medicines = Medicine::whereIn('id', $needed->keys())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $shortages = [];
            foreach ($needed as $medicineId => $qty) {
                $medicine = $medicines[$medicineId];
                if ($medicine->stock < $qty) {
                    $shortages[] = [
                        'medicine'  => $medicine->name,
                        'needed'    => $qty,
                        'available' => $medicine->stock,
                    ];
                }
            }

            // Ada yang kurang: jangan kurangi stok apa pun
            if (! empty($shortages)) {
                return $shortages;
            }

            foreach ($needed as $medicineId => $qty) {
                $medicines[$medicineId]->decrement('stock', $qty);
            }

            $prescription->update(['status' => 'diproses']);

            return [];
        });

        if (! empty($shortages)) {
            return response()->json([
                'message'   => 'Stok obat tidak mencukupi',
                'shortages' => $shortages,
            ], 422);
        }

        return response()->json([
            'message' => 'Resep diproses, stok obat diperbarui',
            'data'    => $prescription->fresh($this->relations),
        ]);
    }

    // PATCH /api/prescriptions/{id}/hold — tandai resep tertunda (stok kurang)
    public function hold($id)
    {
        $prescription = Prescription::find($id);

        if (! $prescription) {
            return response()->json(['message' => 'Resep tidak ditemukan'], 404);
        }

        if ($prescription->status !== 'menunggu') {
            return response()->json([
                'message' => 'Hanya resep berstatus menunggu yang bisa ditandai tertunda',
            ], 422);
        }

        $prescription->update(['status' => 'tertunda']);

        return response()->json([
            'message' => 'Resep ditandai tertunda',
            'data'    => $prescription->fresh($this->relations),
        ]);
    }

    // PATCH /api/prescriptions/{id}/complete — obat diserahkan ke pasien
    public function complete($id)
    {
        $prescription = Prescription::find($id);

        if (! $prescription) {
            return response()->json(['message' => 'Resep tidak ditemukan'], 404);
        }

        if ($prescription->status !== 'diproses') {
            return response()->json([
                'message' => 'Resep harus diproses terlebih dahulu',
            ], 422);
        }

        $prescription->update(['status' => 'selesai']);

        return response()->json([
            'message' => 'Obat sudah diserahkan, resep selesai',
            'data'    => $prescription->fresh($this->relations),
        ]);
    }

    // Cek obat mana yang stoknya kurang (tanpa mengubah data)
    private function findShortages(Prescription $prescription): array
    {
        $shortages = [];

        foreach ($prescription->items as $item) {
            if ($item->medicine->stock < $item->quantity) {
                $shortages[] = [
                    'medicine'  => $item->medicine->name,
                    'needed'    => $item->quantity,
                    'available' => $item->medicine->stock,
                ];
            }
        }

        return $shortages;
    }
}