<?php

namespace App\Http\Requests;

use App\Enums\RolUsuario;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;

class IncidenciaRequest extends FormRequest
{
    public const ESTADOS = ['Abierta', 'En atencion', 'Resuelta'];

    public function authorize(): bool
    {
        $usuario = $this->user();

        if (! $usuario instanceof Usuario) {
            return false;
        }

        // Atender (PATCH) solo el administrador; reportar cualquier rol interno.
        if ($this->isMethod('PATCH')) {
            return $usuario->rol === RolUsuario::Administrador;
        }

        return $usuario->rol !== RolUsuario::Cliente;
    }

    public function rules(): array
    {
        if ($this->isMethod('PATCH')) {
            return [
                'estado' => ['required', 'string', 'in:'.implode(',', self::ESTADOS)],
                'nota_atencion' => ['required_if:estado,Resuelta', 'nullable', 'string', 'max:2000'],
            ];
        }

        return [
            'id_servicio' => ['nullable', 'integer', 'exists:servicio,id_servicio'],
            'descripcion' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_servicio.exists' => 'El servicio seleccionado no existe.',
            'descripcion.required' => 'Describe la incidencia.',
            'descripcion.min' => 'La descripción debe tener al menos 10 caracteres.',
            'descripcion.max' => 'La descripción no puede superar 2000 caracteres.',
            'estado.required' => 'Selecciona el estado de la incidencia.',
            'estado.in' => 'El estado seleccionado no es válido.',
            'nota_atencion.required_if' => 'Escribe una nota de atención para resolver la incidencia.',
        ];
    }
}