<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterClienteRequest;
use App\Http\Resources\ClienteResource;
use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ClienteController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ClienteResource::collection(
            Cliente::query()->orderBy('apellido')->orderBy('nombre')->paginate(25)
        );
    }

    public function store(RegisterClienteRequest $request): JsonResponse
    {
        $data = $request->validated();

        $cliente = DB::transaction(function () use ($data): Cliente {
            $usuario = Usuario::query()->create([
                'usuario' => $data['usuario'],
                'correo' => $data['correo'],
                'contrasena' => $data['contrasena'],
                'rol' => RolUsuario::Cliente,
                'estado' => EstadoUsuario::Activo,
            ]);

            return Cliente::query()->create([
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'documento' => $data['documento'],
                'telefono' => $data['telefono'] ?? null,
                'direccion' => $data['direccion'] ?? null,
                'correo' => $data['correo'],
                'id_usuario' => $usuario->getKey(),
            ]);
        });

        return (new ClienteResource($cliente))->response()->setStatusCode(201);
    }
}
