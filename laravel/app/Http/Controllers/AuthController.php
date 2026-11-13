<?php

namespace App\Http\Controllers;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\RegisterClienteRequest;
use App\Models\Cliente;
use App\Models\Usuario;
use App\Services\AuthenticatesUsuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function createRegistration(): View
    {
        return view('auth.register');
    }

    public function store(LoginRequest $request, AuthenticatesUsuario $authenticator): RedirectResponse
    {
        $usuario = $authenticator->attempt(
            $request->validated('correo'),
            $request->validated('contrasena')
        );

        if (! $usuario) {
            return back()
                ->withErrors(['correo' => 'Credenciales incorrectas o cuenta inactiva.'])
                ->onlyInput('correo');
        }

        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function storeRegistration(RegisterClienteRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $usuario = DB::transaction(function () use ($data): Usuario {
            $usuario = Usuario::query()->create([
                'usuario' => $data['usuario'],
                'correo' => $data['correo'],
                'contrasena' => Hash::make($data['contrasena']),
                'rol' => RolUsuario::Cliente,
                'estado' => EstadoUsuario::Activo,
            ]);

            Cliente::query()->create([
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'documento' => $data['documento'],
                'telefono' => $data['telefono'] ?? null,
                'direccion' => $data['direccion'] ?? null,
                'correo' => $data['correo'],
                'id_usuario' => $usuario->getKey(),
            ]);

            return $usuario;
        });

        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(): RedirectResponse
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }
}
