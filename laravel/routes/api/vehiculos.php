<?php

use App\Http\Controllers\Api\VehiculoController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware([AuthenticateJwt::class, 'role:administrador,recepcionista,cliente'])->group(function (): void {
    Route::get('/vehiculos', [VehiculoController::class, 'index'])->name('api.vehiculos.index');
    Route::post('/vehiculos', [VehiculoController::class, 'store'])->name('api.vehiculos.store');
    Route::get('/vehiculos/{vehiculo}', [VehiculoController::class, 'show'])->name('api.vehiculos.show');
});
