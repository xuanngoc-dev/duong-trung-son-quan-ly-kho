<?php

use App\Http\Controllers\Api\NhaCungCapController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/status', function () {
    return response()->json([
        'success' => true,
        'message' => 'Kết nối Laravel API thành công.',
        'data' => [
            'application' => config('app.name'),
            'timestamp' => now()->toIso8601String(),
        ],
    ]);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
Route::apiResource('nha-cung-cap', NhaCungCapController::class)->only(['index', 'store', 'update', 'destroy']);
