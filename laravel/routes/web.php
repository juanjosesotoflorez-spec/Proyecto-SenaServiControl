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
use App\Http\Controllers\UsuarioAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('inicio');
});

Route::get('/inicio', function () {
    $landingPath = base_path('../index.html');
    abort_unless(is_file($landingPath), 404);

    $html = file_get_contents($landingPath);
    abort_if($html === false, 500);

    $html = str_replace([
        'href="laravel/public/login"',
        'href="laravel/public/register"',
        'href="css/',
        'href="js/',
        'src="css/',
        'src="js/',
        'src="imagenes/',
    ], [
        'href="login"',
        'href="register"',
        'href="inicio/css/',
        'href="inicio/js/',
        'src="inicio/css/',
        'src="inicio/js/',
        'src="inicio/imagenes/',
    ], $html);

    return response($html, 200, [
        'Content-Type' => 'text/html; charset=UTF-8',
    ]);
})->name('inicio');

Route::get('/inicio/{folder}/{file}', function (string $folder, string $file) {
    abort_unless(in_array($folder, ['css', 'js', 'imagenes'], true), 404);

    $root = realpath(base_path('../'.$folder));
    $path = realpath($root.DIRECTORY_SEPARATOR.$file);
    abort_unless(is_string($root) && is_string($path), 404);
    abort_unless(str_starts_with($path, $root.DIRECTORY_SEPARATOR), 404);
    abort_unless(is_file($path), 404);

    $mime = match (pathinfo($path, PATHINFO_EXTENSION)) {
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        default => 'application/octet-stream',
    };

    return response()->file($path, ['Content-Type' => $mime]);
})->where('folder', 'css|js|imagenes')->where('file', '[^/]+');

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

    // Rutas de Reportes (Incluyendo la ruta de exportación del formulario)
    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::post('/reportes/exportar', [ReporteController::class, 'exportar'])->name('reportes.exportar'); // <-- ¡Añadida aquí!
    Route::get('/reportes/pdf/imprimir', [ReporteController::class, 'print'])
        ->middleware('role:administrador,recepcionista')
        ->name('reportes.pdf.print');
    Route::get('/reportes/pdf/servicios', [ReporteController::class, 'pdfServicios'])
        ->middleware('role:administrador,recepcionista')
        ->name('reportes.pdf.servicios');
    Route::get('/reportes/pdf', [ReporteController::class, 'pdf'])
        ->middleware('role:administrador,recepcionista')
        ->name('reportes.pdf');

    Route::middleware('role:administrador')->group(function () {
        Route::get('/clientes/{cliente}/edit', [ClienteController::class, 'edit'])->name('clientes.edit');
        Route::put('/clientes/{cliente}', [ClienteController::class, 'update'])->name('clientes.update');
        Route::delete('/clientes/{cliente}', [ClienteController::class, 'destroy'])->name('clientes.destroy');

        Route::get('/vehiculos/{vehiculo}/edit', [VehiculoController::class, 'edit'])->name('vehiculos.edit');
        Route::put('/vehiculos/{vehiculo}', [VehiculoController::class, 'update'])->name('vehiculos.update');
        Route::delete('/vehiculos/{vehiculo}', [VehiculoController::class, 'destroy'])->name('vehiculos.destroy');

        Route::get('/usuarios', [UsuarioAdminController::class, 'index'])->name('usuarios.index');
        Route::get('/usuarios/{usuario}/edit', [UsuarioAdminController::class, 'edit'])->name('usuarios.edit');
        Route::put('/usuarios/{usuario}', [UsuarioAdminController::class, 'update'])->name('usuarios.update');
        Route::delete('/usuarios/{usuario}', [UsuarioAdminController::class, 'destroy'])->name('usuarios.destroy');
    });
});
