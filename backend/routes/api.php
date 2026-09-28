<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\PolyclinicController;
use App\Http\Controllers\Api\VisitController;
use App\Http\Controllers\Api\QueueController;
use App\Http\Controllers\Api\MedicalRecordController;
use App\Http\Controllers\Api\MedicineController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;


//routes publik
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/polyclinics', [PolyclinicController::class, 'index']);
Route::get('/polyclinics/{id}', [PolyclinicController::class, 'show']);

Route::get('/doctors', [DoctorController::class, 'index']);
Route::get('/doctors/{id}', [DoctorController::class, 'show']);


//routes proteksi (harus login)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/visits', [VisitController::class, 'store']);
    Route::get('/visits/my', [VisitController::class, 'myVisits']);
    Route::get('/visits/{id}/queue', [VisitController::class, 'queueStatus']);

Route::middleware('role:dokter,petugas,admin')->group(function () {
        Route::get('/queues/today', [QueueController::class, 'today']);
        Route::patch('/queues/{id}/call', [QueueController::class, 'call']);
    });

    Route::patch('/queues/{id}/start', [QueueController::class, 'start'])
        ->middleware('role:dokter');

    Route::post('/medical-records', [MedicalRecordController::class, 'store'])
        ->middleware('role:dokter');

    Route::get('/patients/{id}/medical-records', [MedicalRecordController::class, 'history'])
        ->middleware('role:dokter,petugas,admin');

    Route::get('/medicines', [MedicineController::class, 'index'])
        ->middleware('role:dokter,farmasi,petugas,admin');
    Route::patch('/medicines/{id}/stock', [MedicineController::class, 'updateStock'])
        ->middleware('role:farmasi,admin');

    Route::middleware('role:farmasi,admin')->group(function () {
        Route::get('/prescriptions', [PrescriptionController::class, 'index']);
        Route::get('/prescriptions/{id}', [PrescriptionController::class, 'show']);
        Route::patch('/prescriptions/{id}/process', [PrescriptionController::class, 'process']);
        Route::patch('/prescriptions/{id}/hold', [PrescriptionController::class, 'hold']);
        Route::patch('/prescriptions/{id}/complete', [PrescriptionController::class, 'complete']);
    });

    Route::middleware('role:petugas,admin')->group(function () {
        Route::get('/payments', [PaymentController::class, 'index']);
        Route::post('/payments', [PaymentController::class, 'store']);
        Route::patch('/payments/{id}/pay', [PaymentController::class, 'pay']);
    });

    Route::get('/payments/{id}', [PaymentController::class, 'show'])
        ->middleware('role:pasien,petugas,admin');

});