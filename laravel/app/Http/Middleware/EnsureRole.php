<?php

namespace App\Http\Middleware;

use App\Enums\RolUsuario;
use App\Models\Usuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        abort_unless(
            $usuario instanceof Usuario
                && $usuario->rol instanceof RolUsuario
                && in_array($usuario->rol->value, $roles, true),
            403
        );

        return $next($request);
    }
}
