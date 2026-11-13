<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateContactRequest;
use App\Http\Resources\ClienteResource;
use App\Models\Usuario;
use App\Services\UpdateClienteContact;

class ClienteContactoController extends Controller
{
    public function update(UpdateContactRequest $request, UpdateClienteContact $updater): ClienteResource
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();
        $cliente = $updater->handle($usuario, $request->validated());

        return new ClienteResource($cliente);
    }
}
