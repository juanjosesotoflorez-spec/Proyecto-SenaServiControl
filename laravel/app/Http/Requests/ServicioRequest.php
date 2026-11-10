<?php

namespace App\Http\Requests;

use App\Enums\EstadoServicioEnum;
use App\Enums\RolUsuario;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;

class ServicioRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof Usuario && $user->rol === RolUsuario::Recepcionista;
    }

    public function rules(): array
    {
        return [
            'tipo_servicio' => ['required', 'string', 'in:Mantenimiento,Reparacion,Revision general,Diagnostico'],
            'descripcion' => ['required', 'string', 'max:10000'],
            'fecha_ingreso' => ['required', 'date_format:Y-m-d'],
            'fecha_entrega_estimada' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:fecha_ingreso'],
            'estado' => ['required', 'string', 'in:'.EstadoServicioEnum::Pendiente->value],
            'costo' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'id_vehiculo' => ['required', 'integer', 'exists:vehiculo,id_vehiculo'],
            'id_tecnico' => ['required', 'integer', 'exists:tecnico,id_tecnico'],
        ];
    }
}
