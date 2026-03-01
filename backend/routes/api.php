<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);


// Public routes (không cần token)
Route::post('/register', [AuthController::class, 'register']);  
Route::post('/login',    [AuthController::class, 'login']);

// Protected routes (cần token)
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Các route cần đăng nhập mới dùng được
    Route::post('/products',      [ProductController::class, 'store']);
    Route::put('/products/{id}',  [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
});
