<?php

namespace App\Http\Requests\Api;

use App\Enums\RolUsuario;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        $usuario = $this->user();

        return $usuario instanceof Usuario && $usuario->rol === RolUsuario::Cliente;
    }

    public function rules(): array
    {
        $usuario = $this->user();

        return [
            'correo' => [
                'sometimes',
                'required',
                'email',
                'max:100',
                Rule::unique('usuario', 'correo')->ignore($usuario?->getAuthIdentifier(), 'id_usuario'),
            ],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:20'],
            'direccion' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->hasAny(['correo', 'telefono', 'direccion'])) {
                    $validator->errors()->add('contacto', 'Debes proporcionar al menos un dato de contacto.');
                }
            },
        ];
    }
}
