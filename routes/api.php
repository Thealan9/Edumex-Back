<?php

use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\BookController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Warehouseman\InventoryController;
use App\Http\Controllers\Warehouseman\MovementController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\VolumeDiscountController;
use Illuminate\Http\Request;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('catalog', [App\Http\Controllers\User\CatalogController::class, 'index']);



Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/yo', fn(Request $r) => $r->user());
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('admin')->middleware('role:admin')->group(function () {
        // Vista Usuarios
        Route::apiResource('users', UserController::class);
        Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive']);
        Route::put('/users/{user}/change-password', [UserController::class, 'changePassword']);

        Route::apiResource('locations', LocationController::class);
        Route::apiResource('books', BookController::class);
        Route::apiResource('discounts', VolumeDiscountController::class);
        Route::get('reports/inventory', [ReportController::class, 'monthlyInventory']);
        Route::get('reports/sales', [ReportController::class, 'salesSummary']);
    });

    Route::prefix('warehouseman')->middleware('role:warehouseman')->group(function () {
        Route::get('books', [BookController::class, 'index']);
        Route::get('locations', [LocationController::class, 'index']);

        Route::post('inventory/move', [InventoryController::class, 'store']);
        Route::get('movements', [MovementController::class, 'index']);
        Route::get('movements/{id}', [MovementController::class, 'show']);
    });

    Route::prefix('user')->group(function () {
        Route::post('orders', [App\Http\Controllers\User\OrderController::class, 'store']);
    });
});
