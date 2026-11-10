<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehiculoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_vehiculo,
            'placa' => $this->placa,
            'marca' => $this->marca,
            'modelo' => $this->modelo,
            'color' => $this->color,
            'anio' => $this->anio,
            'kilometraje' => $this->kilometraje,
            'cliente' => [
                'id' => $this->cliente->id_cliente,
                'nombre' => $this->cliente->nombre,
                'apellido' => $this->cliente->apellido,
            ],
        ];
    }
}
