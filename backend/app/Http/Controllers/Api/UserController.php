<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // Peran yang boleh dibuat/diedit lewat endpoint ini.
    // Pasien tidak termasuk (daftar sendiri), dokter tidak termasuk (lewat DoctorController).
    private array $manageableRoles = ['admin', 'petugas', 'farmasi'];

    // GET /api/users?role=petugas — daftar pengguna (admin)
    public function index(Request $request)
    {
        $query = User::orderBy('name');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        return response()->json(['data' => $query->get()]);
    }

    // POST /api/users — tambah akun petugas/farmasi/admin (admin)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role'     => ['required', Rule::in($this->manageableRoles)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

        return response()->json([
            'message' => 'Pengguna berhasil ditambahkan',
            'data'    => $user,
        ], 201);
    }

    // PUT /api/users/{id} — ubah akun (admin)
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (! $user || ! in_array($user->role, $this->manageableRoles)) {
            return response()->json(['message' => 'Pengguna tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'     => 'sometimes|required|string|max:255',
            'email'    => [
                'sometimes', 'required', 'email',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => 'nullable|string|min:8',
            'role'     => ['sometimes', 'required', Rule::in($this->manageableRoles)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json([
            'message' => 'Pengguna berhasil diubah',
            'data'    => $user->fresh(),
        ]);
    }

    // DELETE /api/users/{id} — hapus akun (admin)
    public function destroy(Request $request, $id)
    {
        $user = User::find($id);

        if (! $user || ! in_array($user->role, $this->manageableRoles)) {
            return response()->json(['message' => 'Pengguna tidak ditemukan'], 404);
        }

        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak bisa menghapus akun Anda sendiri',
            ], 422);
        }

        $user->delete();

        return response()->json(['message' => 'Pengguna berhasil dihapus']);
    }
}