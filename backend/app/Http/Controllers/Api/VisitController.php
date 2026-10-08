<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Queue;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class VisitController extends Controller
{
    // POST /api/visits — pasien mendaftar kunjungan
    public function store(Request $request)
    {
        $patient = $request->user()->patient;

        if (! $patient) {
            return response()->json([
                'message' => 'Akun ini bukan akun pasien',
            ], 403);
        }

        // "Cek data pasien": kalau belum lengkap, wajib diisi sekarang lalu disimpan (input sekali)
        $needsProfile = $this->profileIncomplete($patient);

        $rules = [
            'doctor_id'          => 'required|exists:doctors,id',
            'doctor_schedule_id' => 'nullable|exists:doctor_schedules,id',
            'complaint'          => 'required|string',
        ];

        if ($needsProfile) {
            $rules += [
                'nik'        => ['required', 'string', 'size:16', Rule::unique('patients', 'nik')->ignore($patient->id)],
                'gender'     => 'required|in:L,P',
                'birth_date' => 'required|date|before:today',
                'address'    => 'required|string',
            ];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $doctor = Doctor::with('polyclinic')->findOrFail($request->doctor_id);

        $result = DB::transaction(function () use ($request, $patient, $doctor, $needsProfile) {
            if ($needsProfile) {
                $patient->update($request->only(['nik', 'gender', 'birth_date', 'address']));
            }

            $visit = Visit::create([
                'patient_id'         => $patient->id,
                'doctor_id'          => $doctor->id,
                'polyclinic_id'      => $doctor->polyclinic_id,
                'doctor_schedule_id' => $request->doctor_schedule_id,
                'visit_date'         => now()->toDateString(),
                'complaint'          => $request->complaint,
                'status'             => 'menunggu',
            ]);

            $queue = Queue::create([
                'visit_id'     => $visit->id,
                'queue_number' => $this->generateQueueNumber($doctor->polyclinic),
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

    private function profileIncomplete($patient): bool
    {
        return empty($patient->nik)
            || empty($patient->gender)
            || empty($patient->birth_date)
            || empty($patient->address);
    }

    // Format nomor antrian: {queue_code}-{urut 3 digit}, reset tiap hari per poli
    private function generateQueueNumber($polyclinic): string
    {
        $today = now()->toDateString();

        $countToday = Queue::whereHas('visit', function ($query) use ($polyclinic, $today) {
            $query->where('polyclinic_id', $polyclinic->id)
                  ->whereDate('visit_date', $today);
        })->count();

        return $polyclinic->queue_code . '-' . str_pad($countToday + 1, 3, '0', STR_PAD_LEFT);
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

        return response()->json(['data' => $visits]);
    }

    // GET /api/visits/{id}/queue — status antrian 1 kunjungan (halaman "Antrian Saya")
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

        return response()->json(['data' => $visit]);
    }
}