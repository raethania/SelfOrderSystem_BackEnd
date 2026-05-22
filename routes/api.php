<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\auth\AuthController; 


Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('products', ProductController::class);

    // Order routes
    Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus']);
});
