<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);


// Public routes (không cần token)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (cần token)
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/update-profile', [AuthController::class, 'updateProfile']);

    // Các route cần đăng nhập mới dùng được
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
});


// Public routes quên mật khẩu
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
// Link này trỏ từ Frontend để bắt đầu redirect sang Google
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
// Link này là callback từ Google trả về
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);




