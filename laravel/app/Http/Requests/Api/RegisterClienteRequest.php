<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'usuario' => ['required', 'string', 'max:50', Rule::unique('usuario', 'usuario')],
            'correo' => ['required', 'email', 'max:100', Rule::unique('usuario', 'correo')],
            'contrasena' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'nombre' => ['required', 'string', 'max:50'],
            'apellido' => ['required', 'string', 'max:50'],
            'documento' => ['required', 'string', 'max:20', Rule::unique('cliente', 'documento')],
            'telefono' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:100'],
        ];
    }
}
