<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

class UpdateClienteContact
{
    public function handle(Usuario $usuario, array $datos): Cliente
    {
        $cliente = Cliente::query()->where('id_usuario', $usuario->getKey())->firstOrFail();

        DB::transaction(function () use ($cliente, $datos, $usuario): void {
            $cliente->update(array_intersect_key($datos, array_flip(['telefono', 'direccion', 'correo'])));

            if (array_key_exists('correo', $datos)) {
                $usuario->update(['correo' => $datos['correo']]);
            }
        });

        return $cliente->refresh();
    }
}
