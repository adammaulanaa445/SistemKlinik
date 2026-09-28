<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Queue;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VisitController extends Controller
{
    // POST /api/visits — pasien mendaftar kunjungan
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id'          => 'required|exists:doctors,id',
            'doctor_schedule_id' => 'nullable|exists:doctor_schedules,id',
            'complaint'          => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $patient = $request->user()->patient;

        if (! $patient) {
            return response()->json([
                'message' => 'Akun ini bukan akun pasien',
            ], 403);
        }

        $doctor = Doctor::with('polyclinic')->findOrFail($request->doctor_id);

        // Gunakan transaction supaya pembuatan visit + queue konsisten (atomic)
        $result = DB::transaction(function () use ($request, $patient, $doctor) {
            $visit = Visit::create([
                'patient_id'         => $patient->id,
                'doctor_id'          => $doctor->id,
                'polyclinic_id'      => $doctor->polyclinic_id,
                'doctor_schedule_id' => $request->doctor_schedule_id,
                'visit_date'         => now()->toDateString(),
                'complaint'          => $request->complaint,
                'status'             => 'menunggu',
            ]);

            $queueNumber = $this->generateQueueNumber($doctor->polyclinic);

            $queue = Queue::create([
                'visit_id'     => $visit->id,
                'queue_number' => $queueNumber,
                'status'       => 'menunggu',
            ]);

            return [$visit, $queue];
        });

        [$visit, $queue] = $result;

        return response()->json([
            'message' => 'Pendaftaran kunjungan berhasil',
            'data'    => [
                'visit' => $visit->load(['doctor.user', 'polyclinic']),
                'queue' => $queue,
            ],
        ], 201);
    }

    // Logic generate nomor antrian: {queue_code}-{nomor urut 3 digit}, reset tiap hari per poli
    private function generateQueueNumber($polyclinic): string
    {
        $today = now()->toDateString();

        $countToday = Queue::whereHas('visit', function ($query) use ($polyclinic, $today) {
            $query->where('polyclinic_id', $polyclinic->id)
                  ->whereDate('visit_date', $today);
        })->count();

        $nextNumber = $countToday + 1;

        return $polyclinic->queue_code . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    // GET /api/visits/my — riwayat kunjungan pasien yang sedang login
    public function myVisits(Request $request)
    {
        $patient = $request->user()->patient;

        if (! $patient) {
            return response()->json([
                'message' => 'Akun ini bukan akun pasien',
            ], 403);
        }

        $visits = Visit::with(['doctor.user', 'polyclinic', 'queue', 'payment'])
            ->where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $visits,
        ]);
    }

    // GET /api/visits/{id}/queue — cek status antrian 1 kunjungan spesifik (untuk halaman "Antrian Saya")
    public function queueStatus(Request $request, $id)
    {
        $patient = $request->user()->patient;

        $visit = Visit::with(['doctor.user', 'polyclinic', 'queue', 'payment'])
            ->where('patient_id', $patient->id)
            ->find($id);

        if (! $visit) {
            return response()->json([
                'message' => 'Kunjungan tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'data' => $visit,
        ]);
    }
}