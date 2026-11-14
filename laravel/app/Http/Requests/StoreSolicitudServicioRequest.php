<?php

namespace App\Http\Requests;

use App\Enums\RolUsuario;
use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSolicitudServicioRequest extends FormRequest
{
    public function authorize(): bool
    {
        $usuario = $this->user();

        return $usuario instanceof Usuario
            && $usuario->rol === RolUsuario::Cliente
            && Cliente::query()->where('id_usuario', $usuario->getKey())->exists();
    }

    public function rules(): array
    {
        $idCliente = Cliente::query()
            ->where('id_usuario', $this->user()->getAuthIdentifier())
            ->value('id_cliente');

        return [
            'id_vehiculo' => [
                'required',
                'integer',
                Rule::exists('vehiculo', 'id_vehiculo')->where('id_cliente', $idCliente),
            ],
            'tipo_servicio' => ['required', 'string', 'in:Mantenimiento,Reparacion,Revision general,Diagnostico'],
            'descripcion' => ['required', 'string', 'max:2000'],
            'fecha_solicitada' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.today()->toDateString(),
                'before_or_equal:'.today()->addYear()->toDateString(),
            ],
        ];
    }
}