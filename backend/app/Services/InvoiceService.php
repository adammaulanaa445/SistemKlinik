<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Visit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    public function generateForPayment(Payment $payment): string
    {
        $visit = $payment->visit->load(['patient.user', 'doctor.user', 'polyclinic', 'medicalRecord.prescription.items.medicine']);
        $billingService = app(BillingService::class);
        $bill = $billingService->buildBill($visit);

        $pdf = Pdf::loadView('invoice', [
            'payment' => $payment,
            'visit' => $visit,
            'bill' => $bill,
        ]);

        $filename = 'invoices/' . $payment->invoice_number . '.pdf';
        Storage::disk('public')->put($filename, $pdf->output());

        $payment->update(['invoice_path' => $filename]);

        return $filename;
    }
}