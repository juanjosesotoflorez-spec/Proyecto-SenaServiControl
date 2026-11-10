<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthenticatesUsuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
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

    public function destroy(): RedirectResponse
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }
}
