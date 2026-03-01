<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

//Route::get('/products', [ProductController::class, 'index']);   
Route::get('/products', function () {
    return response()->json([
        'cors_config' => config('cors'),
        'middleware' => app()->make(\Illuminate\Contracts\Http\Kernel::class),
    ]);
});