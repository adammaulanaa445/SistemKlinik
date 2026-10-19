<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\BillingService;

class MedicalRecordController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'visit_id'                         => 'required|exists:visits,id',
            'examination_result'               => 'required|string|max:5000',
            'diagnosis'                        => 'required|string|max:1000',
            'treatment'                        => 'nullable|string|max:5000',
            'notes'                            => 'nullable|string|max:5000',
            'prescription_items'               => 'nullable|array',
            'prescription_items.*.medicine_id' => 'required|exists:medicines,id',
            'prescription_items.*.quantity'    => 'required|integer|min:1',
            'prescription_items.*.dosage'      => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal','errors' => $validator->errors()], 422);
        }
        $doctor = $request->user()->doctor;
        $visit  = Visit::with(['queue', 'medicalRecord'])->find($request->visit_id);
        if (! $doctor || $visit->doctor_id !== $doctor->id) {
            return response()->json(['message' => 'Kunjungan ini bukan pasien Anda'], 403);
        }
        if ($visit->status !== 'diperiksa') {
            return response()->json(['message' => 'Pemeriksaan harus dimulai terlebih dahulu'], 422);
        }
        if ($visit->medicalRecord) {
            return response()->json(['message' => 'Rekam medis untuk kunjungan ini sudah ada'], 422);
        }
        $record = DB::transaction(function () use ($request, $visit) {
            $record = MedicalRecord::create([
                'visit_id'           => $visit->id,
                'examination_result' => $request->examination_result,
                'diagnosis'          => $request->diagnosis,
                'treatment'          => $request->treatment,
                'notes'              => $request->notes,
            ]);
            if (! empty($request->prescription_items)) {
                $prescription = Prescription::create(['medical_record_id' => $record->id,'status' => 'menunggu']);
                foreach ($request->prescription_items as $item) {
                    $prescription->items()->create(['medicine_id' => $item['medicine_id'],'quantity' => $item['quantity'],'dosage' => $item['dosage']]);
                }
            }
            if ($visit->queue) {
                $visit->queue->update(['status' => 'selesai']);
            }
            $visit->update(['status' => 'selesai']);

            $payment = null;
            if (empty($request->prescription_items)) {
                $payment = app(BillingService::class)->createForVisit($visit);
            }

            if ($payment) {
                $visit->load('patient');
                if ($visit->patient && $visit->patient->insurance_type === 'umum' && empty($payment->invoice_path)) {
                    $path = app(\App\Services\InvoiceService::class)->generateForPayment($payment);
                    $log = app(\App\Services\NotificationService::class)->queueWhatsApp('pasien', $visit->patient->id, 'invoice_created', [
                        'phone' => $visit->patient->phone,
                        'message' => "Invoice {$payment->invoice_number} telah terbit. Total: Rp " . number_format($payment->amount, 0, ',', '.') . ". Unduh: " . url('storage/' . $path),
                    ]);
                    app(\App\Services\NotificationService::class)->sendQueued($log);
                }
            }

            app(\App\Services\ActivityLogger::class)->log($request->user(), 'create', 'medical_record', 'Menyimpan rekam medis', $record);

            return $record;
        });
        return response()->json(['message' => 'Rekam medis berhasil disimpan','data' => $record->load('prescription.items.medicine')], 201);
    }

    public function history($patientId)
    {
        if (! Patient::find($patientId)) {
            return response()->json(['message' => 'Pasien tidak ditemukan'], 404);
        }

        $records = MedicalRecord::with(['visit.doctor.user','visit.polyclinic','prescription.items.medicine'])
            ->whereHas('visit', fn ($q) => $q->where('patient_id', $patientId))
            ->orderByDesc('id')->get();
        return response()->json(['data' => $records]);
    }
}
