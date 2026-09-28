<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Queue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QueueController extends Controller
{
    // GET /api/queues/today — daftar antrian hari ini
    // Dokter hanya melihat pasiennya sendiri. Petugas/admin bisa filter: ?polyclinic_id=1
    public function today(Request $request)
    {
        $user = $request->user();

        $queues = Queue::with(['visit.patient.user', 'visit.doctor.user', 'visit.polyclinic'])
            ->whereHas('visit', function ($q) use ($request, $user) {
                $q->whereDate('visit_date', now()->toDateString());

                if ($user->role === 'dokter') {
                    $q->where('doctor_id', optional($user->doctor)->id);
                }

                if ($request->filled('polyclinic_id')) {
                    $q->where('polyclinic_id', $request->polyclinic_id);
                }
            })
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $queues]);
    }

    // PATCH /api/queues/{id}/call — panggil pasien (menunggu -> dipanggil)
    public function call(Request $request, $id)
    {
        $queue = $this->findQueue($request, $id);

        if (! $queue) {
            return response()->json(['message' => 'Antrian tidak ditemukan'], 404);
        }

        if ($queue->status !== 'menunggu') {
            return response()->json([
                'message' => 'Hanya antrian berstatus menunggu yang bisa dipanggil',
            ], 422);
        }

        $queue->update(['status' => 'dipanggil']);

        return response()->json([
            'message' => 'Pasien dipanggil',
            'data'    => $queue->fresh(),
        ]);
    }

    // PATCH /api/queues/{id}/start — mulai pemeriksaan (dipanggil -> diproses)
    public function start(Request $request, $id)
    {
        $queue = $this->findQueue($request, $id);

        if (! $queue) {
            return response()->json(['message' => 'Antrian tidak ditemukan'], 404);
        }

        if ($queue->status !== 'dipanggil') {
            return response()->json([
                'message' => 'Pasien harus dipanggil terlebih dahulu',
            ], 422);
        }

        DB::transaction(function () use ($queue) {
            $queue->update(['status' => 'diproses']);
            $queue->visit->update(['status' => 'diperiksa']);
        });

        return response()->json([
            'message' => 'Pemeriksaan dimulai',
            'data'    => $queue->fresh(['visit']),
        ]);
    }

    // Cari antrian, dokter hanya boleh mengakses antrian pasiennya sendiri
    private function findQueue(Request $request, $id)
    {
        $queue = Queue::with('visit')->find($id);

        if (! $queue) {
            return null;
        }

        $user = $request->user();

        if ($user->role === 'dokter' && $queue->visit->doctor_id !== optional($user->doctor)->id) {
            return null;
        }

        return $queue;
    }
}