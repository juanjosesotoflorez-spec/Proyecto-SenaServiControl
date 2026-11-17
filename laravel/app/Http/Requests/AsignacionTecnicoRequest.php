<?php

namespace App\Http\Requests;

use App\Enums\RolUsuario;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;

class AsignacionTecnicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $usuario = $this->user();

        return $usuario instanceof Usuario
            && in_array($usuario->rol, [RolUsuario::Administrador, RolUsuario::Recepcionista], true);
    }

    public function rules(): array
    {
        return [
            'id_tecnico_nuevo' => ['required', 'integer', 'exists:tecnico,id_tecnico'],
            'motivo' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_tecnico_nuevo.required' => 'Selecciona el técnico responsable.',
            'id_tecnico_nuevo.integer' => 'El técnico seleccionado no es válido.',
            'id_tecnico_nuevo.exists' => 'El técnico seleccionado no existe.',
            'motivo.required' => 'Indica el motivo de la asignación.',
            'motivo.min' => 'El motivo debe tener al menos 5 caracteres.',
            'motivo.max' => 'El motivo no puede superar 500 caracteres.',
        ];
    }
}