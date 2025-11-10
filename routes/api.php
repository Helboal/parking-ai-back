<?php

use App\Http\Controllers\Admin\DocumentTypeController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Admin routes
    Route::prefix('admin')->group(function () {
        Route::apiResource('document-types', DocumentTypeController::class);
    });
});
