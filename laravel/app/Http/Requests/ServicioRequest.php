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
            'costo' => ['required', 'numeric', 'regex:/^\d+$/', 'min:0', 'max:99999999'],
            'id_vehiculo' => ['required', 'integer', 'exists:vehiculo,id_vehiculo'],
            'id_tecnico' => ['required', 'integer', 'exists:tecnico,id_tecnico'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_servicio.required' => 'Selecciona el tipo de servicio.',
            'tipo_servicio.string' => 'El tipo de servicio debe ser un texto válido.',
            'tipo_servicio.in' => 'El tipo de servicio seleccionado no es válido.',
            'descripcion.required' => 'La descripción del trabajo es obligatoria.',
            'descripcion.string' => 'La descripción debe ser un texto válido.',
            'descripcion.max' => 'La descripción no puede superar 10.000 caracteres.',
            'fecha_ingreso.required' => 'La fecha de ingreso es obligatoria.',
            'fecha_ingreso.string' => 'La fecha de ingreso debe ser válida.',
            'fecha_ingreso.date_format' => 'La fecha de ingreso debe tener un formato válido.',
            'fecha_entrega_estimada.string' => 'La fecha estimada debe ser válida.',
            'fecha_entrega_estimada.date_format' => 'La fecha estimada debe tener un formato válido.',
            'fecha_entrega_estimada.after_or_equal' => 'La fecha estimada no puede ser anterior a la fecha de ingreso.',
            'estado.required' => 'Selecciona el estado inicial del servicio.',
            'estado.string' => 'El estado inicial debe ser un texto válido.',
            'estado.in' => 'El estado inicial seleccionado no es válido.',
            'costo.required' => 'El costo es obligatorio.',
            'costo.numeric' => 'El costo debe ser un valor numérico.',
            'costo.regex' => 'El costo solo puede contener dígitos, sin puntos ni signos.',
            'costo.min' => 'El costo no puede ser negativo.',
            'costo.max' => 'El costo no puede superar 99.999.999.',
            'id_vehiculo.required' => 'Selecciona el vehículo del servicio.',
            'id_vehiculo.integer' => 'El vehículo seleccionado no es válido.',
            'id_vehiculo.exists' => 'El vehículo seleccionado no existe.',
            'id_tecnico.required' => 'Selecciona el técnico responsable.',
            'id_tecnico.integer' => 'El técnico seleccionado no es válido.',
            'id_tecnico.exists' => 'El técnico seleccionado no existe.',
        ];
    }
}
