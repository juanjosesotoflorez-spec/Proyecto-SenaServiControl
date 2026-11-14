<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ClientePerfilController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EntregaVehiculoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\SolicitudServicioController;
use App\Http\Controllers\VehiculoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->away('../../index.html');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
    Route::get('/register', [AuthController::class, 'createRegistration'])->name('register');
    Route::post('/register', [AuthController::class, 'storeRegistration'])
        ->middleware('throttle:5,1')
        ->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/perfil', [ClientePerfilController::class, 'edit'])->name('perfil.edit');
    Route::put('/perfil', [ClientePerfilController::class, 'update'])->name('perfil.update');
    Route::post('/perfil/foto', [ClientePerfilController::class, 'updatePhoto'])->name('perfil.foto.update');
    Route::get('/perfil/foto', [ClientePerfilController::class, 'photo'])->name('perfil.foto');

    Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
    Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');

    Route::get('/vehiculos', [VehiculoController::class, 'index'])->name('vehiculos.index');
    Route::post('/vehiculos', [VehiculoController::class, 'store'])->name('vehiculos.store');

    Route::get('/servicios', [ServicioController::class, 'index'])->name('servicios.index');
    Route::get('/servicios/{servicio}', [ServicioController::class, 'show'])->name('servicios.show');
    Route::post('/servicios', [ServicioController::class, 'store'])->name('servicios.store');
    Route::post('/servicios/{servicio}/entrega', [EntregaVehiculoController::class, 'store'])->name('servicios.delivery.store');
    Route::post('/servicios/{servicio}/aceptar', [ServicioController::class, 'acceptAssignment'])->name('servicios.accept');
    Route::post('/servicios/{servicio}/avances', [ServicioController::class, 'recordProgress'])->name('servicios.progress');

    Route::get('/solicitudes', [SolicitudServicioController::class, 'index'])->name('solicitudes.index');
    Route::post('/solicitudes', [SolicitudServicioController::class, 'store'])->name('solicitudes.store');
    Route::post('/solicitudes/{solicitud}/aceptar', [SolicitudServicioController::class, 'accept'])->name('solicitudes.accept');

    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('/reportes/pdf/imprimir', [ReporteController::class, 'print'])->name('reportes.pdf.print');
    Route::get('/reportes/pdf', [ReporteController::class, 'pdf'])->name('reportes.pdf');
});
