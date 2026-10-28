<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\GenreController;
use App\Http\Controllers\Api\MovieController;
use App\Http\Controllers\Api\ScreeningController;
use Illuminate\Support\Facades\Route;

// ===== Гость =====
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::get('/genres', [GenreController::class, 'index']);
Route::get('/movies', [MovieController::class, 'index']);
Route::get('/movies/{movie}', [MovieController::class, 'show']);
Route::get('/sessions', [ScreeningController::class, 'index']);
Route::get('/sessions/{screening}', [ScreeningController::class, 'show']);
Route::get('/sessions/{screening}/seats', [ScreeningController::class, 'seats']);

// ===== Любой авторизованный =====
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

// ===== Зритель (и админ): покупка и личный кабинет =====
Route::middleware(['auth:sanctum', 'role:viewer,admin'])->group(function () {
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::delete('/bookings/{id}', [BookingController::class, 'destroy']);
});

// ===== Только админ =====
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    // Временный маршрут для проверки роли, потом удалим
    Route::get('/ping', fn () => response()->json(['message' => 'admin ok']));
});