<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('api.auth.login');

Route::middleware(AuthenticateJwt::class)->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'show'])->name('api.auth.me');
    Route::post('/auth/logout', [AuthController::class, 'destroy'])->name('api.auth.logout');
});

require __DIR__.'/api/clientes.php';
require __DIR__.'/api/contacto.php';
