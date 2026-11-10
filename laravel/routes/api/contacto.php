<?php

use App\Http\Controllers\Api\ClienteContactoController;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Support\Facades\Route;

Route::middleware([AuthenticateJwt::class, 'role:cliente'])->group(function (): void {
    Route::put('/me/contact', [ClienteContactoController::class, 'update'])
        ->name('api.me.contact.update');
});
