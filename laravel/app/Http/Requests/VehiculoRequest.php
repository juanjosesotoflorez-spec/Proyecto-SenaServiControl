<?php

namespace App\Http\Requests;

use App\Enums\RolUsuario;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof Usuario
            && in_array($user->rol, [RolUsuario::Administrador, RolUsuario::Recepcionista, RolUsuario::Cliente], true);
    }

    public function rules(): array
    {
        $staffCanSelectClient = in_array($this->user()?->rol, [RolUsuario::Administrador, RolUsuario::Recepcionista], true);

        return [
            'placa' => ['required', 'string', 'max:10', Rule::unique('vehiculo', 'placa')],
            'marca' => ['nullable', 'string', 'max:50'],
            'modelo' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:30'],
            'anio' => ['nullable', 'integer', 'between:1886,'.((int) date('Y') + 1)],
            'kilometraje' => ['nullable', 'integer', 'min:0'],
            'id_cliente' => [$staffCanSelectClient ? 'required' : 'prohibited', 'integer', 'exists:cliente,id_cliente'],
        ];
    }
}
