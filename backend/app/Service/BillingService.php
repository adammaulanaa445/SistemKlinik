<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Visit;

class BillingService
{
    // Buat tagihan otomatis untuk 1 kunjungan (kalau belum ada)
    public function createForVisit(Visit $visit): Payment
    {
        $existing = Payment::where('visit_id', $visit->id)->first();

        if ($existing) {
            return $existing;
        }

        $visit->load('medicalRecord.prescription.items.medicine');
        $bill = $this->buildBill($visit);

        return Payment::create([
            'visit_id'     => $visit->id,
            'payment_code' => $this->generatePaymentCode(),
            'amount'       => $bill['total'],
            'status'       => 'belum_bayar',
        ]);
    }

    // Rincian tagihan: biaya konsultasi + obat di resep
    public function buildBill(Visit $visit): array
    {
        $consultationFee = (float) config('clinic.consultation_fee');
        $medicines       = [];
        $medicineTotal   = 0;

        $prescription = optional($visit->medicalRecord)->prescription;

        if ($prescription) {
            foreach ($prescription->items as $item) {
                $subtotal = $item->quantity * $item->medicine->price;

                $medicines[] = [
                    'medicine' => $item->medicine->name,
                    'quantity' => $item->quantity,
                    'price'    => (float) $item->medicine->price,
                    'subtotal' => (float) $subtotal,
                ];

                $medicineTotal += $subtotal;
            }
        }

        return [
            'consultation_fee' => $consultationFee,
            'medicines'        => $medicines,
            'medicine_total'   => (float) $medicineTotal,
            'total'            => $consultationFee + $medicineTotal,
        ];
    }

    // Format: INV-20261008-001 (nomor urut reset tiap hari)
    private function generatePaymentCode(): string
    {
        $today = now();

        $count = Payment::whereDate('created_at', $today->toDateString())->count() + 1;

        return 'INV-' . $today->format('Ymd') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}