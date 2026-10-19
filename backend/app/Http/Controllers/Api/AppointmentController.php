<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Queue;
use App\Models\Visit;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AppointmentController extends Controller
{
    private int $durationPerPatient = 10; // menit

    private array $dayMap = [0 => 'minggu', 1 => 'senin', 2 => 'selasa', 3 => 'rabu', 4 => 'kamis', 5 => 'jumat', 6 => 'sabtu'];

    // GET /api/appointments/availability?doctor_id=&date=
    public function availability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required|exists:doctors,id',
            'date'      => 'required|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $doctor = Doctor::with('polyclinic')->findOrFail($request->doctor_id);
        $date = $request->date;
        $day = $this->dayMap[(int) \Carbon\Carbon::parse($date)->dayOfWeek];

        $schedule = DoctorSchedule::where('doctor_id', $doctor->id)->where('day', $day)->first();

        if (! $schedule) {
            return response()->json(['message' => 'Dokter tidak memiliki jadwal pada tanggal tersebut'], 404);
        }

        $bookedCount = Visit::where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', $date)
            ->whereNotIn('appointment_status', ['cancelled', 'no_show'])
            ->count();

        $remaining = max(0, $schedule->quota - $bookedCount);

        $estimates = [];
        for ($i = 1; $i <= $schedule->quota; $i++) {
            $estimates[] = [
                'queue_number' => $doctor->polyclinic->queue_code . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'estimated_time' => \Carbon\Carbon::parse($schedule->start_time)->addMinutes(($i - 1) * $this->durationPerPatient)->format('H:i'),
                'available' => $i > $bookedCount,
            ];
        }

        return response()->json([
            'data' => [
                'doctor' => $doctor->load('user'),
                'polyclinic' => $doctor->polyclinic,
                'schedule' => $schedule,
                'date' => $date,
                'booked' => $bookedCount,
                'remaining' => $remaining,
                'duration_per_patient' => $this->durationPerPatient,
                'estimates' => $estimates,
            ],
        ]);
    }

    // POST /api/appointments
    public function store(Request $request)
    {
        $patient = $request->user()->patient;

        if ($request->filled('patient_id') && in_array($request->user()->role, ['petugas', 'admin'])) {
            $patient = \App\Models\Patient::with('user')->find($request->patient_id);
        }

        if (! $patient) {
            return response()->json(['message' => 'Akun ini bukan akun pasien'], 403);
        }

        $validator = Validator::make($request->all(), [
            'patient_id'         => 'nullable|exists:patients,id',
            'doctor_id'          => 'required|exists:doctors,id',
            'appointment_date'   => 'required|date|after_or_equal:today',
            'complaint'          => 'required|string',
            'doctor_schedule_id' => 'nullable|exists:doctor_schedules,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $doctor = Doctor::with('polyclinic')->findOrFail($request->doctor_id);
        $date = $request->appointment_date;
        $day = $this->dayMap[(int) \Carbon\Carbon::parse($date)->dayOfWeek];

        $schedule = DoctorSchedule::where('doctor_id', $doctor->id)->where('day', $day)->first();
        if (! $schedule) {
            return response()->json(['message' => 'Dokter tidak memiliki jadwal pada tanggal tersebut'], 422);
        }

        $needsProfile = empty($patient->nik) || empty($patient->gender) || empty($patient->birth_date) || empty($patient->address);

        if ($needsProfile) {
            $profileValidator = Validator::make($request->all(), [
                'nik'        => ['required', 'string', 'size:16'],
                'gender'     => 'required|in:L,P',
                'birth_date' => 'required|date|before:today',
                'address'    => 'required|string',
            ]);
            if ($profileValidator->fails()) {
                return response()->json(['message' => 'Validasi gagal', 'errors' => $profileValidator->errors()], 422);
            }
        }

        $result = DB::transaction(function () use ($request, $patient, $doctor, $schedule, $date, $needsProfile) {
            if ($needsProfile) {
                $patient->update($request->only(['nik', 'gender', 'birth_date', 'address']));
            }

            $bookedCount = Visit::where('doctor_id', $doctor->id)
                ->whereDate('appointment_date', $date)
                ->whereNotIn('appointment_status', ['cancelled', 'no_show'])
                ->lockForUpdate()
                ->count();

            if ($bookedCount >= $schedule->quota) {
                return null;
            }

            $queueNumber = $doctor->polyclinic->queue_code . '-' . str_pad($bookedCount + 1, 3, '0', STR_PAD_LEFT);
            $estimated = \Carbon\Carbon::parse($schedule->start_time)->addMinutes($bookedCount * $this->durationPerPatient)->format('H:i:s');

            $visit = Visit::create([
                'patient_id'         => $patient->id,
                'doctor_id'          => $doctor->id,
                'polyclinic_id'      => $doctor->polyclinic_id,
                'doctor_schedule_id' => $schedule->id,
                'visit_date'         => $date,
                'appointment_date'   => $date,
                'visit_type'         => 'appointment',
                'appointment_status' => 'booked',
                'estimated_start_time' => $estimated,
                'complaint'          => $request->complaint,
                'status'             => 'booked',
            ]);

            $queue = Queue::create([
                'visit_id'     => $visit->id,
                'queue_number' => $queueNumber,
                'status'       => 'booked',
            ]);

            return [$visit, $queue, $estimated];
        });

        if (! $result) {
            return response()->json(['message' => 'Kuota jadwal sudah penuh'], 422);
        }

        [$visit, $queue, $estimated] = $result;

        app(ActivityLogger::class)->log($request->user(), 'create', 'appointment', 'Membuat appointment', $visit);

        return response()->json([
            'message' => 'Appointment berhasil dibuat',
            'data' => [
                'visit' => $visit->load(['doctor.user', 'polyclinic']),
                'queue' => $queue,
                'estimated_start_time' => $estimated,
            ],
        ], 201);
    }

    // GET /api/appointments/my
    public function myAppointments(Request $request)
    {
        $patient = $request->user()->patient;
        if (! $patient) {
            return response()->json(['message' => 'Akun ini bukan akun pasien'], 403);
        }

        $visits = Visit::with(['doctor.user', 'polyclinic', 'queue'])
            ->where('patient_id', $patient->id)
            ->where('visit_type', 'appointment')
            ->orderBy('appointment_date')
            ->get();

        return response()->json(['data' => $visits]);
    }

    // PATCH /api/appointments/{id}/check-in
    public function checkIn(Request $request, $id)
    {
        $visit = Visit::with('queue')->find($id);
        if (! $visit || $visit->visit_type !== 'appointment') {
            return response()->json(['message' => 'Appointment tidak ditemukan'], 404);
        }

        if ($visit->appointment_status !== 'booked') {
            return response()->json(['message' => 'Appointment tidak dapat check-in'], 422);
        }

        DB::transaction(function () use ($visit) {
            $visit->update([
                'status' => 'menunggu',
                'appointment_status' => 'checked_in',
            ]);
            if ($visit->queue) {
                $visit->queue->update(['status' => 'menunggu']);
            }
        });

        app(ActivityLogger::class)->log($request->user(), 'check_in', 'appointment', 'Check-in appointment', $visit);

        return response()->json(['message' => 'Check-in berhasil', 'data' => $visit->fresh(['queue'])]);
    }

    // PATCH /api/appointments/{id}/cancel
    public function cancel(Request $request, $id)
    {
        $visit = Visit::find($id);
        if (! $visit || $visit->visit_type !== 'appointment') {
            return response()->json(['message' => 'Appointment tidak ditemukan'], 404);
        }

        if (in_array($visit->appointment_status, ['completed', 'cancelled'])) {
            return response()->json(['message' => 'Appointment tidak dapat dibatalkan'], 422);
        }

        $visit->update([
            'status' => 'batal',
            'appointment_status' => 'cancelled',
            'cancellation_reason' => $request->input('reason'),
        ]);

        if ($visit->queue) {
            $visit->queue->update(['status' => 'batal']);
        }

        app(ActivityLogger::class)->log($request->user(), 'cancel', 'appointment', 'Membatalkan appointment', $visit);

        return response()->json(['message' => 'Appointment dibatalkan', 'data' => $visit->fresh()]);
    }

    // GET /api/appointments/today
    public function today(Request $request)
    {
        $user = $request->user();
        $query = Visit::with(['patient.user', 'doctor.user', 'polyclinic', 'queue'])
            ->where('visit_type', 'appointment')
            ->whereDate('appointment_date', now()->toDateString());

        if ($user->role === 'dokter') {
            $query->where('doctor_id', optional($user->doctor)->id);
        }

        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        $visits = $query->orderBy('estimated_start_time')->get();

        return response()->json(['data' => $visits]);
    }
}