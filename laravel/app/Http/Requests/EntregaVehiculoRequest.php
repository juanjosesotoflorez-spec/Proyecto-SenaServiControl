<?php

namespace App\Http\Requests;

use App\Enums\RolUsuario;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;

class EntregaVehiculoRequest extends FormRequest
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
            'id_recepcionista' => $this->user()?->rol === RolUsuario::Administrador
                ? ['required', 'integer', 'exists:recepcionista,id_recepcionista']
                : ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_recepcionista.required' => 'Selecciona el recepcionista responsable de la entrega.',
            'id_recepcionista.integer' => 'El recepcionista seleccionado no es válido.',
            'id_recepcionista.exists' => 'El recepcionista seleccionado no existe.',
            'id_recepcionista.prohibited' => 'No puedes cambiar el recepcionista asignado a tu cuenta.',
        ];
    }
}
