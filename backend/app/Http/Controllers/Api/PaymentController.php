<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['visit.patient.user', 'visit.polyclinic'])->orderByDesc('id');
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), ['visit_id' => 'required|exists:visits,id']);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal','errors' => $validator->errors()], 422);
        }
        $visit = Visit::with(['payment','medicalRecord.prescription.items.medicine'])->find($request->visit_id);
        if ($visit->payment) {
            return response()->json(['message' => 'Tagihan untuk kunjungan ini sudah dibuat'], 422);
        }
        if ($visit->status !== 'selesai' || ! $visit->medicalRecord) {
            return response()->json(['message' => 'Pemeriksaan belum selesai'], 422);
        }
        $prescription = $visit->medicalRecord->prescription;
        if ($prescription && $prescription->status !== 'selesai') {
            return response()->json(['message' => 'Resep belum selesai diproses farmasi'], 422);
        }
        $bill = $this->buildBill($visit);
        $payment = DB::transaction(function () use ($visit, $bill) {
            return Payment::create([
                'visit_id' => $visit->id,
                'payment_code' => $this->generatePaymentCodeLocked(),
                'amount' => $bill['total'],
                'status' => 'belum_bayar',
            ]);
        });
        return response()->json(['message' => 'Tagihan berhasil dibuat','data' => $payment,'bill' => $bill], 201);
    }

    public function pay(Request $request, $id)
    {
        $validator = Validator::make($request->all(), ['method' => 'required|in:tunai,transfer,qris']);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal','errors' => $validator->errors()], 422);
        }
        $payment = Payment::find($id);
        if (! $payment) {
            return response()->json(['message' => 'Pembayaran tidak ditemukan'], 404);
        }
        if ($payment->status === 'lunas') {
            return response()->json(['message' => 'Tagihan ini sudah lunas'], 422);
        }
        $payment->update(['method' => $request->input('method'),'status' => 'lunas','paid_at' => now()]);
        return response()->json(['message' => 'Pembayaran berhasil dicatat','data' => $payment->fresh()]);
    }

    public function show(Request $request, $id)
    {
        $payment = Payment::with(['visit.patient.user','visit.doctor.user','visit.polyclinic','visit.medicalRecord.prescription.items.medicine'])->find($id);
        if (! $payment) {
            return response()->json(['message' => 'Pembayaran tidak ditemukan'], 404);
        }
        $user = $request->user();
        if ($user->role === 'pasien' && optional($payment->visit->patient)->user_id !== $user->id) {
            return response()->json(['message' => 'Pembayaran tidak ditemukan'], 404);
        }
        return response()->json(['data' => $payment,'bill' => $this->buildBill($payment->visit)]);
    }

    private function buildBill(Visit $visit): array
    {
        $consultationFee = (float) config('clinic.consultation_fee');
        $medicines = [];
        $medicineTotal = 0;
        $prescription = optional($visit->medicalRecord)->prescription;
        if ($prescription) {
            foreach ($prescription->items as $item) {
                if (! $item->medicine) continue;
                $subtotal = $item->quantity * $item->medicine->price;
                $medicines[] = ['medicine' => $item->medicine->name,'quantity' => $item->quantity,'price' => (float) $item->medicine->price,'subtotal' => (float) $subtotal];
                $medicineTotal += $subtotal;
            }
        }
        return ['consultation_fee' => $consultationFee,'medicines' => $medicines,'medicine_total' => (float) $medicineTotal,'total' => $consultationFee + $medicineTotal];
    }

    private function generatePaymentCodeLocked(): string
    {
        $today = now();
        $count = Payment::whereDate('created_at', $today->toDateString())->lockForUpdate()->count() + 1;
        return 'INV-' . $today->format('Ymd') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
