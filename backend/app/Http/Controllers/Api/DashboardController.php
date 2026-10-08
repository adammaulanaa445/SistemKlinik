<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\Queue;
use App\Models\Visit;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // GET /api/dashboard/doctor — ringkasan untuk dokter yang sedang login
    public function doctor(Request $request)
    {
        $doctor = $request->user()->doctor;

        if (! $doctor) {
            return response()->json(['message' => 'Akun ini bukan akun dokter'], 403);
        }

        $today = now()->toDateString();

        $visitsToday = Visit::where('doctor_id', $doctor->id)
            ->whereDate('visit_date', $today);

        return response()->json([
            'data' => [
                'total_hari_ini'    => (clone $visitsToday)->count(),
                'menunggu'          => (clone $visitsToday)->where('status', 'menunggu')->count(),
                'sedang_diperiksa'  => (clone $visitsToday)->where('status', 'diperiksa')->count(),
                'selesai'           => (clone $visitsToday)->where('status', 'selesai')->count(),
                'antrian_terbaru'   => Queue::with(['visit.patient.user'])
                    ->whereHas('visit', fn ($q) => $q->where('doctor_id', $doctor->id)->whereDate('visit_date', $today))
                    ->whereIn('status', ['menunggu', 'dipanggil', 'diproses'])
                    ->orderBy('id')
                    ->limit(20)
                    ->get(),
            ],
        ]);
    }

    // GET /api/dashboard/pharmacy — ringkasan untuk farmasi
    public function pharmacy(Request $request)
    {
        $today = now()->toDateString();

        $prescriptionsToday = Prescription::whereHas(
            'medicalRecord.visit',
            fn ($q) => $q->whereDate('visit_date', $today)
        );

        return response()->json([
            'data' => [
                'menunggu'     => (clone $prescriptionsToday)->where('status', 'menunggu')->count(),
                'diproses'     => (clone $prescriptionsToday)->where('status', 'diproses')->count(),
                'tertunda'     => (clone $prescriptionsToday)->where('status', 'tertunda')->count(),
                'selesai'      => (clone $prescriptionsToday)->where('status', 'selesai')->count(),
                'stok_menipis' => \App\Models\Medicine::where('status', 'aktif')
                    ->where('stock', '<', 20)
                    ->orderBy('stock')
                    ->get(['id', 'name', 'stock', 'unit']),
            ],
        ]);
    }

    // GET /api/dashboard/admin — ringkasan menyeluruh untuk admin
    public function admin(Request $request)
    {
        $today = now()->toDateString();

        $visitsToday = Visit::whereDate('visit_date', $today);

        return response()->json([
            'data' => [
                'total_pasien'          => Patient::count(),
                'total_dokter'          => Doctor::count(),
                'kunjungan_hari_ini'    => (clone $visitsToday)->count(),
                'antrian_menunggu'      => Queue::whereHas('visit', fn ($q) => $q->whereDate('visit_date', $today))
                                                ->where('status', 'menunggu')->count(),
                'pendapatan_hari_ini'   => (float) Payment::where('status', 'lunas')
                                                ->whereDate('paid_at', $today)
                                                ->sum('amount'),
                'pembayaran_tertunda'   => Payment::where('status', 'belum_bayar')->count(),
                'kunjungan_per_poli_hari_ini' => (clone $visitsToday)
                    ->join('polyclinics', 'visits.polyclinic_id', '=', 'polyclinics.id')
                    ->selectRaw('polyclinics.name as poli, count(*) as total')
                    ->groupBy('polyclinics.name')
                    ->get(),
                'kunjungan_7_hari' => Visit::selectRaw('visit_date, count(*) as total')
                    ->where('visit_date', '>=', now()->subDays(6)->toDateString())
                    ->groupBy('visit_date')
                    ->orderBy('visit_date')
                    ->get(),
            ],
        ]);
    }
}   