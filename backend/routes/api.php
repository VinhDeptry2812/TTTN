<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProductVariantController;
use App\Models\Category;

use App\Http\Controllers\CategoryController;

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
// Biến thể: lấy danh sách variant (public - không cần đăng nhập)
Route::get('/products/{productId}/variants', [ProductVariantController::class, 'index']);

// Danh mục sản phẩm (public)
Route::get('/categories', [CategoryController::class, 'index']);


// Public routes (không cần token)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (cần token)
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/update-profile', [AuthController::class, 'updateProfile']);


});

// Public route cho Admin
Route::post('/admin/login', [AdminController::class, 'login']);

// Route cho Quản trị viên (Admin)
Route::middleware('auth:admin-api')->group(function () {
    Route::get('/admin/me', [AdminController::class, 'me']);
    Route::post('/admin/logout', [AdminController::class, 'logout']);
    Route::get('/admin/dashboard', [AdminController::class, 'index']);
    Route::post('/products/create', [ProductController::class, 'store']);
    Route::match(['POST', 'PUT'], '/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    // Product Variants (Admin)
    Route::post('/products/{productId}/variants', [ProductVariantController::class, 'store']);
    Route::match(['POST', 'PUT'], '/products/{productId}/variants/{variantId}', [ProductVariantController::class, 'update']);
    Route::delete('/products/{productId}/variants/{variantId}', [ProductVariantController::class, 'destroy']);

    // Categories (Admin)
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    Route::match(['POST', 'PUT'], '/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    // Nếu muốn check role cụ thể (superadmin mới được vào)
    Route::middleware('is_superadmin')->group(function () {
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




