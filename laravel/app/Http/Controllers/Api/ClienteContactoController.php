<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateContactRequest;
use App\Http\Resources\ClienteResource;
use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

class ClienteContactoController extends Controller
{
    public function update(UpdateContactRequest $request): ClienteResource
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();
        $cliente = Cliente::query()->where('id_usuario', $usuario->getKey())->firstOrFail();
        $datos = $request->validated();

        $cliente = DB::transaction(function () use ($cliente, $datos, $usuario): Cliente {
            $cliente->update(array_intersect_key($datos, array_flip(['telefono', 'direccion', 'correo'])));

            if (array_key_exists('correo', $datos)) {
                $usuario->update(['correo' => $datos['correo']]);
            }

            return $cliente->refresh();
        });

        return new ClienteResource($cliente);
    }
}
