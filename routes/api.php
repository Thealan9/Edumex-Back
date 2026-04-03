<?php

use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\BookController;
use App\Http\Controllers\Admin\EbookController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\User\AddressController;
use App\Http\Controllers\Warehouseman\InventoryController;
use App\Http\Controllers\Warehouseman\MovementController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\VolumeDiscountController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use Illuminate\Http\Request;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [UserController::class, 'store']);
Route::get('catalog', [App\Http\Controllers\User\CatalogController::class, 'index']);
Route::get('catalog/{id}', [App\Http\Controllers\User\CatalogController::class, 'show']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);


Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/yo', fn(Request $r) => $r->user());
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('admin')->middleware('role:admin')->group(function () {
        // Vista Usuarios
        Route::apiResource('users', UserController::class);
        Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive']);
        Route::put('/users/{user}/change-password', [UserController::class, 'changePassword']);
        Route::get('books/nameBooks', [BookController::class, 'nameBooks']);

        Route::apiResource('locations', LocationController::class);
        Route::apiResource('books', BookController::class);
        Route::post('books/{id}/image', [BookController::class, 'updateImage']);
        Route::apiResource('ebooks', EbookController::class);
        Route::patch('ebooks/{ebook}/status', [EbookController::class, 'toggleStatus']);
        Route::patch('books/{book}/status', [BookController::class, 'toggleStatus']);

        Route::apiResource('discounts', VolumeDiscountController::class);
        Route::get('reports/inventory', [ReportController::class, 'monthlyInventory']);
        Route::get('reports/sales', [ReportController::class, 'salesSummary']);
        Route::get('reports/financial', [ReportController::class, 'getFinancialReport']);
        Route::get('warehousemen-list', [UserController::class, 'getWarehousemen']);
        Route::post('purchase-orders', [PurchaseOrderController::class, 'store']);
        Route::post('output-orders', [App\Http\Controllers\Admin\OutputOrderController::class, 'store']);
        Route::get('books-locations/{book_id}', [InventoryController::class, 'getLocationsByBook']);

        Route::get('dashboard-stats', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'getStats']);


    });

    Route::prefix('warehouseman')->middleware('role:warehouseman')->group(function () {
        Route::get('books', [BookController::class, 'index']);
        Route::get('locations', [LocationController::class, 'index']);

        Route::post('inventory/move', [InventoryController::class, 'store']);
        Route::get('inventory/history', [InventoryController::class, 'index']);
        Route::get('movements', [MovementController::class, 'index']);
        Route::get('movements/{id}', [MovementController::class, 'show']);

        Route::get('pending-despatch', [App\Http\Controllers\User\OrderController::class, 'pendingDespatch']);
        Route::post('orders/{id}/dispatch', [App\Http\Controllers\User\OrderController::class, 'dispatch']);
        Route::get('pending-purchases', [PurchaseOrderController::class, 'pendingForWarehouse']);
        Route::get('pending-outputs', [App\Http\Controllers\Warehouseman\OutputOrderController::class, 'pending']);
        Route::get('books-locations/{book_id}', [InventoryController::class, 'getLocationsByBook']);
    });

    Route::prefix('user')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::put('/users/{user}/change-password', [UserController::class, 'changePassword']);
        Route::get('discounts', [App\Http\Controllers\User\CatalogController::class, 'discounts']);

        Route::post('orders', [App\Http\Controllers\User\OrderController::class, 'store']);
        Route::get('my-orders', [App\Http\Controllers\User\OrderController::class, 'myOrders']);
        Route::get('addresses', [AddressController::class, 'index']);
        Route::post('addresses', [AddressController::class, 'store']);
        Route::put('addresses/{id}', [AddressController::class, 'update']);
        Route::delete('addresses/{id}', [AddressController::class, 'destroy']);
    });
});
