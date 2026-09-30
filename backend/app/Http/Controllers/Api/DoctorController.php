<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    // GET /api/doctors — daftar semua dokter (public)
    // Bisa difilter berdasarkan poli: /api/doctors?polyclinic_id=1
    public function index(Request $request)
    {
        $query = Doctor::with(['user', 'polyclinic']);

        if ($request->has('polyclinic_id')) {
            $query->where('polyclinic_id', $request->polyclinic_id);
        }

        $doctors = $query->get();

        return response()->json([
            'data' => $doctors,
        ]);
    }

    // GET /api/doctors/{id} — detail 1 dokter beserta jadwalnya (public)
    public function show($id)
    {
        $doctor = Doctor::with(['user', 'polyclinic', 'schedules'])->find($id);

        if (! $doctor) {
            return response()->json([
                'message' => 'Dokter tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'data' => $doctor,
        ]);
    }

        // POST /api/doctors — tambah dokter + akun login (admin)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email',
            'password'       => 'required|string|min:8',
            'polyclinic_id'  => 'required|exists:polyclinics,id',
            'doctor_code'    => 'required|string|max:20|unique:doctors,doctor_code',
            'specialization' => 'required|string|max:255',
            'phone'          => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $doctor = DB::transaction(function () use ($request) {
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role'     => 'dokter',
            ]);

            return Doctor::create([
                'user_id'        => $user->id,
                'polyclinic_id'  => $request->polyclinic_id,
                'doctor_code'    => $request->doctor_code,
                'specialization' => $request->specialization,
                'phone'          => $request->phone,
            ]);
        });

        return response()->json([
            'message' => 'Dokter berhasil ditambahkan',
            'data'    => $doctor->load(['user', 'polyclinic']),
        ], 201);
    }

    // PUT /api/doctors/{id} — ubah dokter (admin)
    public function update(Request $request, $id)
    {
        $doctor = Doctor::with('user')->find($id);

        if (! $doctor) {
            return response()->json(['message' => 'Dokter tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'           => 'sometimes|required|string|max:255',
            'email'          => [
                'sometimes', 'required', 'email',
                Rule::unique('users', 'email')->ignore($doctor->user_id),
            ],
            'password'       => 'nullable|string|min:8',
            'polyclinic_id'  => 'sometimes|required|exists:polyclinics,id',
            'doctor_code'    => [
                'sometimes', 'required', 'string', 'max:20',
                Rule::unique('doctors', 'doctor_code')->ignore($doctor->id),
            ],
            'specialization' => 'sometimes|required|string|max:255',
            'phone'          => 'sometimes|required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        DB::transaction(function () use ($doctor, $data) {
            $userData = array_intersect_key($data, array_flip(['name', 'email']));

            if (! empty($data['password'])) {
                $userData['password'] = Hash::make($data['password']);
            }

            if (! empty($userData)) {
                $doctor->user->update($userData);
            }

            $doctorData = array_intersect_key(
                $data,
                array_flip(['polyclinic_id', 'doctor_code', 'specialization', 'phone'])
            );

            if (! empty($doctorData)) {
                $doctor->update($doctorData);
            }

            // Kalau dokter pindah poli, jadwalnya ikut pindah
            if (isset($data['polyclinic_id'])) {
                $doctor->schedules()->update(['polyclinic_id' => $data['polyclinic_id']]);
            }
        });

        return response()->json([
            'message' => 'Data dokter berhasil diubah',
            'data'    => $doctor->fresh(['user', 'polyclinic']),
        ]);
    }

    // DELETE /api/doctors/{id} — hapus dokter + akunnya (admin)
    public function destroy($id)
    {
        $doctor = Doctor::with('user')->find($id);

        if (! $doctor) {
            return response()->json(['message' => 'Dokter tidak ditemukan'], 404);
        }

        if ($doctor->visits()->exists()) {
            return response()->json([
                'message' => 'Dokter sudah memiliki riwayat kunjungan, tidak bisa dihapus',
            ], 422);
        }

        DB::transaction(function () use ($doctor) {
            $user = $doctor->user;

            $doctor->schedules()->delete();
            $doctor->delete();
            $user->delete();
        });

        return response()->json(['message' => 'Dokter berhasil dihapus']);
    }
}