<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use App\Models\Prescription;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MedicalRecordController extends Controller
{
    // POST /api/medical-records — dokter menyimpan hasil pemeriksaan (+ resep)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'visit_id'                         => 'required|exists:visits,id',
            'examination_result'               => 'required|string',
            'diagnosis'                        => 'required|string',
            'treatment'                        => 'nullable|string',
            'notes'                            => 'nullable|string',
            'prescription_items'               => 'nullable|array',
            'prescription_items.*.medicine_id' => 'required|exists:medicines,id',
            'prescription_items.*.quantity'    => 'required|integer|min:1',
            'prescription_items.*.dosage'      => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $doctor = $request->user()->doctor;
        $visit  = Visit::with(['queue', 'medicalRecord'])->find($request->visit_id);

        // Dokter hanya boleh memeriksa pasiennya sendiri
        if (! $doctor || $visit->doctor_id !== $doctor->id) {
            return response()->json(['message' => 'Kunjungan ini bukan pasien Anda'], 403);
        }

        if ($visit->status !== 'diperiksa') {
            return response()->json([
                'message' => 'Pemeriksaan harus dimulai terlebih dahulu',
            ], 422);
        }

        if ($visit->medicalRecord) {
            return response()->json([
                'message' => 'Rekam medis untuk kunjungan ini sudah ada',
            ], 422);
        }

        $record = DB::transaction(function () use ($request, $visit) {
            $record = MedicalRecord::create([
                'visit_id'           => $visit->id,
                'examination_result' => $request->examination_result,
                'diagnosis'          => $request->diagnosis,
                'treatment'          => $request->treatment,
                'notes'              => $request->notes,
            ]);

            // Resep hanya dibuat kalau dokter mengisi obat
            if (! empty($request->prescription_items)) {
                $prescription = Prescription::create([
                    'medical_record_id' => $record->id,
                    'status'            => 'menunggu',
                ]);

                foreach ($request->prescription_items as $item) {
                    $prescription->items()->create([
                        'medicine_id' => $item['medicine_id'],
                        'quantity'    => $item['quantity'],
                        'dosage'      => $item['dosage'],
                    ]);
                }
            }

            // Pemeriksaan selesai
            $visit->queue->update(['status' => 'selesai']);
            $visit->update(['status' => 'selesai']);

            return $record;
        });

        return response()->json([
            'message' => 'Rekam medis berhasil disimpan',
            'data'    => $record->load('prescription.items.medicine'),
        ], 201);
    }

    // GET /api/patients/{id}/medical-records — riwayat medis 1 pasien
    public function history($patientId)
    {
        $records = MedicalRecord::with([
                'visit.doctor.user',
                'visit.polyclinic',
                'prescription.items.medicine',
            ])
            ->whereHas('visit', fn ($q) => $q->where('patient_id', $patientId))
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $records]);
    }
}