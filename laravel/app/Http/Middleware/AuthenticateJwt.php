<?php

namespace App\Http\Middleware;

use App\Enums\EstadoUsuario;
use App\Models\Usuario;
use App\Services\JwtTokenService;
use Closure;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use UnexpectedValueException;

class AuthenticateJwt
{
    public function __construct(private readonly JwtTokenService $tokens) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $token = $request->bearerToken();

        if (! $token) {
            return $this->unauthenticated();
        }

        try {
            $claims = $this->tokens->decode($token);
        } catch (DomainException|UnexpectedValueException|\InvalidArgumentException) {
            return $this->unauthenticated();
        }

        if ($this->tokens->isRevoked($claims->jti)) {
            return $this->unauthenticated();
        }

        $usuario = Usuario::query()->find($claims->sub);

        if (
            ! $usuario
            || $usuario->estado !== EstadoUsuario::Activo
            || $usuario->rol->value !== $claims->role
        ) {
            return $this->unauthenticated();
        }

        $request->attributes->set('jwt.claims', $claims);
        $request->setUserResolver(static fn (): Usuario => $usuario);

        return $next($request);
    }

    private function unauthenticated(): JsonResponse
    {
        return response()->json(['message' => 'Token ausente, inválido o expirado.'], 401);
    }
}
