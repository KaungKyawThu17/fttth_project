<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerTicketController;
use App\Http\Controllers\Api\TechnicianJobController;
use App\Http\Controllers\Api\TechnicianJobPhotoController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1');

Route::middleware('auth:api')->group(function (): void {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('customer')->group(function (): void {
        Route::get('/ticket-categories', [CustomerTicketController::class, 'categories']);
        Route::get('/tickets', [CustomerTicketController::class, 'index']);
        Route::post('/tickets', [CustomerTicketController::class, 'store']);
        Route::get('/tickets/{ticket}', [CustomerTicketController::class, 'show']);
    });

    Route::prefix('technician')->group(function (): void {
        Route::get('/jobs', [TechnicianJobController::class, 'index']);
        Route::get('/jobs/{technicianJob}', [TechnicianJobController::class, 'show']);
        Route::post('/jobs/{technicianJob}/start', [TechnicianJobController::class, 'start']);
        Route::post('/jobs/{technicianJob}/photos', [TechnicianJobPhotoController::class, 'store']);
        Route::post('/jobs/{technicianJob}/complete', [TechnicianJobController::class, 'complete']);
    });
});
