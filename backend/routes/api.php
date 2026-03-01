<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/products', [ProductController::class, 'index']); 
// Thêm route test riêng
Route::get('/cors-test', function () {
    return response()->json(config('cors'));
});

Route::get('/version', function () {
    return response()->json(['version' => '2.0', 'time' => now()]);
});

// routes/api.php
Route::get('/cors-test2', function () {
    return response()->json(['ok' => true])
        ->header('Access-Control-Allow-Origin', 'https://tttn-2.onrender.com')
        ->header('X-Custom-Test', 'hello');
});