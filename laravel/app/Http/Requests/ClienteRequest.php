<?php

namespace App\Http\Requests;

use App\Enums\RolUsuario;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof Usuario
            && in_array($user->rol, [RolUsuario::Administrador, RolUsuario::Recepcionista], true);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:50'],
            'apellido' => ['required', 'string', 'max:50'],
            'documento' => ['required', 'string', 'max:20', Rule::unique('cliente', 'documento')],
            'telefono' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:100'],
            'correo' => ['nullable', 'email', 'max:100'],
        ];
    }
}
