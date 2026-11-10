<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\SolicitudServicioController;
use App\Http\Controllers\VehiculoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
    Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');

    Route::get('/vehiculos', [VehiculoController::class, 'index'])->name('vehiculos.index');
    Route::post('/vehiculos', [VehiculoController::class, 'store'])->name('vehiculos.store');

    Route::get('/servicios', [ServicioController::class, 'index'])->name('servicios.index');
    Route::post('/servicios', [ServicioController::class, 'store'])->name('servicios.store');
    Route::post('/servicios/{servicio}/aceptar', [ServicioController::class, 'acceptAssignment'])->name('servicios.accept');
    Route::post('/servicios/{servicio}/avances', [ServicioController::class, 'recordProgress'])->name('servicios.progress');

    Route::get('/solicitudes', [SolicitudServicioController::class, 'index'])->name('solicitudes.index');
    Route::post('/solicitudes', [SolicitudServicioController::class, 'store'])->name('solicitudes.store');
    Route::post('/solicitudes/{solicitud}/aceptar', [SolicitudServicioController::class, 'accept'])->name('solicitudes.accept');

    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('/reportes/pdf/imprimir', [ReporteController::class, 'print'])->name('reportes.pdf.print');
    Route::get('/reportes/pdf', [ReporteController::class, 'pdf'])->name('reportes.pdf');
});
