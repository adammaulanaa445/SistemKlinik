<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DoctorScheduleController extends Controller
{
    // POST /api/schedules — tambah jadwal praktik (admin)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id'  => 'required|exists:doctors,id',
            'day'        => 'required|in:senin,selasa,rabu,kamis,jumat,sabtu,minggu',
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i|after:start_time',
            'quota'      => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $doctor = Doctor::find($request->doctor_id);
        $start  = $request->start_time . ':00';
        $end    = $request->end_time . ':00';

        if ($this->hasOverlap($doctor->id, $request->day, $start, $end)) {
            return response()->json([
                'message' => 'Jadwal bertabrakan dengan jadwal lain di hari yang sama',
            ], 422);
        }

        $schedule = DoctorSchedule::create([
            'doctor_id'     => $doctor->id,
            'polyclinic_id' => $doctor->polyclinic_id,
            'day'           => $request->day,
            'start_time'    => $start,
            'end_time'      => $end,
            'quota'         => $request->input('quota') ?? 20,
        ]);

        return response()->json([
            'message' => 'Jadwal berhasil ditambahkan',
            'data'    => $schedule,
        ], 201);
    }

    // PUT /api/schedules/{id} — ubah jadwal (admin)
    public function update(Request $request, $id)
    {
        $schedule = DoctorSchedule::find($id);

        if (! $schedule) {
            return response()->json(['message' => 'Jadwal tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'day'        => 'sometimes|required|in:senin,selasa,rabu,kamis,jumat,sabtu,minggu',
            'start_time' => 'sometimes|required|date_format:H:i',
            'end_time'   => 'sometimes|required|date_format:H:i',
            'quota'      => 'sometimes|required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $day   = $request->input('day', $schedule->day);
        $start = $request->filled('start_time') ? $request->start_time . ':00' : $schedule->start_time;
        $end   = $request->filled('end_time') ? $request->end_time . ':00' : $schedule->end_time;

        if ($end <= $start) {
            return response()->json([
                'message' => 'Jam selesai harus setelah jam mulai',
            ], 422);
        }

        if ($this->hasOverlap($schedule->doctor_id, $day, $start, $end, $schedule->id)) {
            return response()->json([
                'message' => 'Jadwal bertabrakan dengan jadwal lain di hari yang sama',
            ], 422);
        }

        $schedule->update([
            'day'        => $day,
            'start_time' => $start,
            'end_time'   => $end,
            'quota'      => $request->input('quota', $schedule->quota),
        ]);

        return response()->json([
            'message' => 'Jadwal berhasil diubah',
            'data'    => $schedule->fresh(),
        ]);
    }

    // DELETE /api/schedules/{id} — hapus jadwal (admin)
    public function destroy($id)
    {
        $schedule = DoctorSchedule::find($id);

        if (! $schedule) {
            return response()->json(['message' => 'Jadwal tidak ditemukan'], 404);
        }

        $schedule->delete();

        return response()->json(['message' => 'Jadwal berhasil dihapus']);
    }

    // Cek apakah jam praktik bertabrakan dengan jadwal lain dokter yang sama di hari yang sama
    private function hasOverlap($doctorId, $day, $start, $end, $ignoreId = null): bool
    {
        return DoctorSchedule::where('doctor_id', $doctorId)
            ->where('day', $day)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->exists();
    }
}