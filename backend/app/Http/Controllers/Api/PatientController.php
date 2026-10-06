<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $query = Patient::with('user')->orderBy('id', 'desc');
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($qq) use ($search) {
                    $qq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
                })->orWhere('nik', 'like', "%{$search}%");
            });
        }
        return response()->json(['data' => $query->get()]);
    }

    public function show($id)
    {
        $patient = Patient::with('user')->withCount('visits')->find($id);
        if (! $patient) return response()->json(['message' => 'Pasien tidak ditemukan'], 404);
        return response()->json(['data' => $patient]);
    }
}
