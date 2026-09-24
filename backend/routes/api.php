<?php

use App\Http\Controllers\Api\DanhMucNguyenVatLieuController;
use App\Http\Controllers\Api\LoNguyenVatLieuController;
use App\Http\Controllers\Api\NhaCungCapController;
use App\Http\Controllers\Api\PhieuKiemTraIqcController;
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
Route::post('danh-muc-nguyen-vat-lieu/anh', [DanhMucNguyenVatLieuController::class, 'uploadImage']);
Route::apiResource('danh-muc-nguyen-vat-lieu', DanhMucNguyenVatLieuController::class)->only(['index', 'store', 'update', 'destroy']);
Route::apiResource('lo-nguyen-vat-lieu', LoNguyenVatLieuController::class)->only(['index', 'store', 'update', 'destroy']);
Route::post('phieu-kiem-tra-iqc/anh', [PhieuKiemTraIqcController::class, 'uploadImage']);
Route::apiResource('phieu-kiem-tra-iqc', PhieuKiemTraIqcController::class)->only(['index', 'store', 'update', 'destroy']);
