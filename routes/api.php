<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

// Auth Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);
    });
});

// Protected Routes
Route::middleware('auth:api')->group(function () {
    Route::apiResource('orders', OrderController::class);
    Route::get('payments',           [PaymentController::class, 'index']);
    Route::post('payments/process',  [PaymentController::class, 'process']);
    Route::get('payments/{payment}', [PaymentController::class, 'show']);
});