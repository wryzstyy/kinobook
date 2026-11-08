<?php

use App\Http\Controllers\Api\Admin\HallController as AdminHallController;
use App\Http\Controllers\Api\Admin\MovieController as AdminMovieController;
use App\Http\Controllers\Api\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Api\Admin\ScreeningController as AdminScreeningController;
use App\Http\Controllers\Api\AiController;
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

// ===== Зритель (и админ): покупка, кабинет, рекомендации =====
Route::middleware(['auth:sanctum', 'role:viewer,admin'])->group(function () {
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::delete('/bookings/{id}', [BookingController::class, 'destroy']);
    Route::post('/bookings/{id}/pay', [BookingController::class, 'pay'])->middleware('throttle:10,1');
    Route::post('/bookings/{id}/resend', [BookingController::class, 'resend'])->middleware('throttle:3,1');

    Route::get('/recommendations', [AiController::class, 'recommendations'])->middleware('throttle:10,1');
});

// ===== Только админ =====
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/ping', fn () => response()->json(['message' => 'admin ok']));

    Route::apiResource('movies', AdminMovieController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('halls', AdminHallController::class);
    Route::apiResource('sessions', AdminScreeningController::class)
        ->parameters(['sessions' => 'screening'])
        ->except('show');

    Route::get('/reports/occupancy', [AdminReportController::class, 'occupancy']);
    Route::post('/ai/movie-description', [AiController::class, 'movieDescription'])->middleware('throttle:10,1');
});