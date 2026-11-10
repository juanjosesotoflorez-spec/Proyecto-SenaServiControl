<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $usuario = Usuario::query()
            ->where('correo', $request->validated('correo'))
            ->where('estado', 'activo')
            ->first();

        if (! $usuario || ! $this->passwordIsValid($usuario, $request->validated('contrasena'))) {
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

    private function passwordIsValid(Usuario $usuario, string $password): bool
    {
        $storedPassword = (string) $usuario->getRawOriginal('contrasena');
        $isHashedPassword = password_get_info($storedPassword)['algo'] !== null;
        $isValid = $isHashedPassword
            ? Hash::check($password, $storedPassword)
            : hash_equals($storedPassword, $password);

        if ($isValid && (! $isHashedPassword || Hash::needsRehash($storedPassword))) {
            $usuario->contrasena = $password;
            $usuario->save();
        }

        return $isValid;
    }
}
