<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoUsuario;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Models\Usuario;
use App\Services\AuthenticatesUsuario;
use App\Services\JwtTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function store(LoginRequest $request, AuthenticatesUsuario $authenticator, JwtTokenService $tokens): JsonResponse
    {
        $usuario = $authenticator->attempt(
            $request->validated('correo'),
            $request->validated('contrasena')
        );

        if (! $usuario || $usuario->estado !== EstadoUsuario::Activo) {
            return response()->json(['message' => 'Credenciales incorrectas o cuenta inactiva.'], 401);
        }

        return response()->json($tokens->issue($usuario));
    }

    public function show(Request $request): JsonResponse
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();

        return response()->json([
            'id' => $usuario->getKey(),
            'username' => $usuario->usuario,
            'email' => $usuario->correo,
            'role' => $usuario->rol->value,
        ]);
    }

    public function destroy(Request $request, JwtTokenService $tokens): JsonResponse
    {
        $claims = $request->attributes->get('jwt.claims');
        $tokens->revoke($claims->jti, $claims->exp);

        return response()->json(['message' => 'Sesión de API cerrada.']);
    }
}
