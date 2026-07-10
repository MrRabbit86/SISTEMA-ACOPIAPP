<?php

use App\Http\Controllers\Web\CategoriaController;
use App\Http\Controllers\Web\ComprobanteController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\LoginController;
use App\Http\Controllers\Web\MiOfertaController;
use App\Http\Controllers\Web\OfertaController;
use App\Http\Controllers\Web\RegistroController;
use App\Http\Controllers\Web\ReporteController;
use App\Http\Controllers\Web\TransaccionController;
use App\Http\Controllers\Web\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::get('/manifest.json', function () {
    return response()->file(
        public_path('manifest.webmanifest'),
        ['Content-Type' => 'application/manifest+json'],
    );
})->name('manifest');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:login.web')->name('login.store');
    Route::post('/login/registrar', [RegistroController::class, 'store'])->name('registro.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:empresa,admin')->prefix('ofertas')->group(function () {
        Route::get('/', [OfertaController::class, 'mapa'])->name('ofertas.mapa');
        Route::get('/listado', [OfertaController::class, 'listado'])->name('ofertas.listado');
        Route::get('/{oferta}', [OfertaController::class, 'show'])->name('ofertas.show');
    });

    Route::middleware('role:empresa,admin')->group(function () {
        Route::get('/transacciones', [TransaccionController::class, 'index'])->name('transacciones.index');
        Route::post('/ofertas/{oferta}/transaccion', [TransaccionController::class, 'store'])->name('ofertas.transaccion.store');
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/pdf', [ReporteController::class, 'exportar'])->name('reportes.pdf');
    });

    Route::middleware('role:empresa,admin,ciudadano')->group(function () {
        Route::get('/comprobantes/{transaccion}', [ComprobanteController::class, 'mostrar'])->name('comprobantes.mostrar');
    });

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('admin.usuarios');
        Route::post('/usuarios/{user}/aprobar', [UsuarioController::class, 'aprobar'])->name('admin.usuarios.aprobar');
        Route::post('/usuarios/{user}/rechazar', [UsuarioController::class, 'rechazar'])->name('admin.usuarios.rechazar');
        Route::get('/categorias', [CategoriaController::class, 'index'])->name('admin.categorias');
        Route::post('/categorias', [CategoriaController::class, 'store'])->name('admin.categorias.store');
        Route::delete('/categorias/{categoria}', [CategoriaController::class, 'destroy'])->name('admin.categorias.destroy');
    });

    Route::middleware('role:ciudadano')->prefix('mis-ofertas')->group(function () {
        Route::get('/', [MiOfertaController::class, 'index'])->name('mis-ofertas');
        Route::post('/', [MiOfertaController::class, 'store'])->name('mis-ofertas.store');
        Route::post('/{oferta}/cancelar', [MiOfertaController::class, 'cancelar'])->name('mis-ofertas.cancelar');
    });
});