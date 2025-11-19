<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\ProcurementRequestController;

// AUTH
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // Dosen + Admin Lab (create + my requests)
    Route::middleware('role:dosen,admin_lab')->group(function () {
        Route::post('/procurement-requests', [ProcurementRequestController::class, 'store']);
        Route::get('/procurement-requests/my', [ProcurementRequestController::class, 'myRequests']);
    });

    // Admin jurusan (all + approve/reject)
    Route::middleware('role:admin_jurusan')->group(function () {
        Route::get('/procurement-requests', [ProcurementRequestController::class, 'index']);
        Route::put('/procurement-requests/{procurement}', [ProcurementRequestController::class, 'update']);
    });

    // ========================================
    // LOAN ROUTES (PEMINJAMAN)
    // ========================================

    // Dosen + Admin Lab
    Route::middleware('role:dosen,admin_lab')->group(function () {
        Route::post('/loans', [LoanController::class, 'store']);
        Route::get('/loans/my', [LoanController::class, 'myLoans']);
        Route::put('/loans/{id}', [LoanController::class, 'update']);
        Route::delete('/loans/{id}', [LoanController::class, 'destroy']);
    });

    // Admin Jurusan
    Route::middleware('role:admin_jurusan')->group(function () {
        Route::get('/loans', [LoanController::class, 'index']);
    });

});
