<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\ComprobanteController;
use App\Http\Controllers\Api\OfertaController;
use App\Http\Controllers\Api\ReporteController;
use App\Http\Controllers\Api\TransaccionController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login.api');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::get('/categorias', [CategoriaController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/ofertas/mias', [OfertaController::class, 'mias']);
    Route::apiResource('ofertas', OfertaController::class)
        ->only(['index', 'show', 'store'])
        ->names('api.ofertas');
    Route::post('/ofertas/{oferta}/cancelar', [OfertaController::class, 'cancelar']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/transacciones', [TransaccionController::class, 'index']);
    Route::post('/transacciones', [TransaccionController::class, 'store']);
    Route::get('/transacciones/{transaccion}', [TransaccionController::class, 'show']);
    Route::get('/transacciones/{transaccion}/comprobante', [ComprobanteController::class, 'descargar']);
    Route::get('/mis-transacciones', [TransaccionController::class, 'mias']);
    Route::get('/reportes', [ReporteController::class, 'index']);
    Route::get('/reportes/pdf', [ReporteController::class, 'pdf']);
});

Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
    Route::get('/usuarios', [AdminController::class, 'usuarios']);
    Route::post('/usuarios/{user}/aprobar', [AdminController::class, 'aprobar']);
    Route::post('/usuarios/{user}/rechazar', [AdminController::class, 'rechazar']);
});