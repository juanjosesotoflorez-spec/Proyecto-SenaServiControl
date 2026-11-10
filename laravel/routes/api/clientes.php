<?php

use App\Http\Controllers\Api\ClienteController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::post('/register', [ClienteController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('api.register');

Route::middleware([AuthenticateJwt::class, 'role:administrador,recepcionista'])->group(function (): void {
    Route::get('/clientes', [ClienteController::class, 'index'])->name('api.clientes.index');
});
