<?php

use App\Http\Controllers\Api\ApiColorController;
use App\Http\Controllers\Api\ApiDashboardController;
use App\Http\Controllers\Api\ApiGuideController;
use App\Http\Controllers\Api\ApiInvoiceController;
use App\Http\Controllers\Api\ApiProductionController;
use App\Http\Controllers\Api\ApiPurchaseOrderController;
use App\Http\Controllers\Api\ApiRoleController;
use App\Http\Controllers\Api\ApiUserController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rutas organizadas por Lógica del Sistema.
| Se dividen en Públicas (Auth/Registro) y Privadas/Protegidas (CRUD, Auth Session).
|
*/

// =====================================================================
// RUTAS PÚBLICAS (No requieren token)
// =====================================================================

// Autenticación
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('register', [AuthController::class, 'register'])->name('register');
});

// =====================================================================
// RUTAS PRIVADAS (Requieren Autenticación / Token JWT)
// =====================================================================

Route::middleware('jwt.verify')->group(function () {

    // Autenticación - Acciones de sesión
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
    });

    // Dashboard
    Route::get('/dashboard', [ApiDashboardController::class, 'index']);

    // Gestión de Roles
    Route::prefix('roles')->group(function () {
        Route::get('/', [ApiRoleController::class, 'index']);
        Route::post('/', [ApiRoleController::class, 'store']);
        Route::put('/{rol}', [ApiRoleController::class, 'update']);
    });

    // Gestión de Órdenes de Compra
    Route::prefix('purchase-orders')->group(function () {
        Route::get('/', [ApiPurchaseOrderController::class, 'index']);
        Route::post('/', [ApiPurchaseOrderController::class, 'store']);
        Route::put('/{purchaseOrder}', [ApiPurchaseOrderController::class, 'update']);
    });

    // Gestión de Producciones
    Route::prefix('productions')->group(function () {
        Route::get('/', [ApiProductionController::class, 'index']);
        Route::post('/', [ApiProductionController::class, 'store']);
        Route::put('/{production}', [ApiProductionController::class, 'update']);
    });

    // Gestión de Guías
    Route::prefix('guides')->group(function () {
        Route::get('/', [ApiGuideController::class, 'index']);
        Route::post('/', [ApiGuideController::class, 'store']);
        Route::put('/{guide}', [ApiGuideController::class, 'update']);
    });

    // Gestión de Facturas
    Route::prefix('invoices')->group(function () {
        Route::get('/', [ApiInvoiceController::class, 'index']);
        Route::post('/', [ApiInvoiceController::class, 'store']);
        Route::put('/{invoice}', [ApiInvoiceController::class, 'update']);
    });

    // Gestión de Colores
    Route::prefix('colores')->group(function () {
        Route::get('/', [ApiColorController::class, 'index']);
        Route::post('/', [ApiColorController::class, 'store']);
        Route::put('/{color}', [ApiColorController::class, 'update']);
    });

    // Gestión de Usuarios
    Route::prefix('usuarios')->group(function () {
        Route::get('/', [ApiUserController::class, 'index']);
        Route::post('/', [ApiUserController::class, 'store']);
        Route::put('/{usuario}', [ApiUserController::class, 'update']);
    });

});