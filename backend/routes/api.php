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

    
});

// Route cho Quản trị viên (Admin)
Route::middleware('auth:admin-api')->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // Nếu muốn check role cụ thể (superadmin mới được vào)
    Route::middleware('is_superadmin')->group(function() {
        //
    });
});



// Public routes quên mật khẩu
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
// Link này trỏ từ Frontend để bắt đầu redirect sang Google
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
// Link này là callback từ Google trả về
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);




