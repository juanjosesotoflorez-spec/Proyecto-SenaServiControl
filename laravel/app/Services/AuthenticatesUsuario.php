<?php

namespace App\Services;

use App\Enums\EstadoUsuario;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class AuthenticatesUsuario
{
    public function attempt(string $email, string $password): ?Usuario
    {
        $usuario = Usuario::query()
            ->where('correo', $email)
            ->where('estado', EstadoUsuario::Activo->value)
            ->first();

        if (! $usuario || ! $this->passwordIsValid($usuario, $password)) {
            return null;
        }

        return $usuario;
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
