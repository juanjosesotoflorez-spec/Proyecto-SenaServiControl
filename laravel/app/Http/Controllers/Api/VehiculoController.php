<?php

namespace App\Http\Controllers\Api;

use App\Enums\RolUsuario;
use App\Http\Controllers\Controller;
use App\Http\Requests\VehiculoRequest;
use App\Http\Resources\VehiculoResource;
use App\Models\Cliente;
use App\Models\Usuario;
use App\Models\Vehiculo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VehiculoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();
        $vehiculos = Vehiculo::query()->with('cliente');

        if ($usuario->rol === RolUsuario::Cliente) {
            $cliente = Cliente::query()->where('id_usuario', $usuario->getKey())->firstOrFail();
            $vehiculos->where('id_cliente', $cliente->getKey());
        }

        return VehiculoResource::collection($vehiculos->orderBy('placa')->paginate(25));
    }

    public function store(VehiculoRequest $request): JsonResponse
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();
        $datos = $request->validated();

        if ($usuario->rol === RolUsuario::Cliente) {
            $cliente = Cliente::query()->where('id_usuario', $usuario->getKey())->firstOrFail();
            $datos['id_cliente'] = $cliente->getKey();
        }

        $vehiculo = Vehiculo::query()->create($datos)->load('cliente');

        return (new VehiculoResource($vehiculo))->response()->setStatusCode(201);
    }

    public function show(Request $request, Vehiculo $vehiculo): VehiculoResource
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();

        if ($usuario->rol === RolUsuario::Cliente) {
            $cliente = Cliente::query()->where('id_usuario', $usuario->getKey())->firstOrFail();
            abort_unless((int) $vehiculo->id_cliente === (int) $cliente->getKey(), 404);
        }

        return new VehiculoResource($vehiculo->load('cliente'));
    }
}
