<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/products', [ProductController::class, 'index']); 
// Thêm route test riêng
Route::get('/cors-test', function () {
    return response()->json(config('cors'));
});