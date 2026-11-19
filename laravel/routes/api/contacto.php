<?php

use App\Http\Controllers\Api\PublicContactController;
use App\Http\Controllers\Api\ClienteContactoController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::post('/contacto', [PublicContactController::class, 'store'])
    ->middleware('throttle:3,1')
    ->name('api.contacto.store');

Route::middleware([AuthenticateJwt::class, 'role:cliente'])->group(function (): void {
    Route::put('/me/contact', [ClienteContactoController::class, 'update'])
        ->name('api.me.contact.update');
});
