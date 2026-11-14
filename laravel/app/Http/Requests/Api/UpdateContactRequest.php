<?php

namespace App\Http\Requests\Api;

use App\Enums\RolUsuario;
use App\Models\Cliente;
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
        $clienteId = $usuario instanceof Usuario
            ? Cliente::query()->where('id_usuario', $usuario->getKey())->value('id_cliente')
            : null;

        return [
            'correo' => [
                'sometimes',
                'required',
                'email:rfc',
                'max:100',
                Rule::unique('usuario', 'correo')->ignore($usuario?->getAuthIdentifier(), 'id_usuario'),
                Rule::unique('cliente', 'correo')->ignore($clienteId, 'id_cliente'),
            ],
            'telefono' => ['sometimes', 'nullable', 'string', 'digits:10'],
            'direccion' => ['sometimes', 'nullable', 'string', 'min:5', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'Ingresa un correo electrónico válido.',
            'correo.max' => 'El correo no puede superar 100 caracteres.',
            'correo.unique' => 'Este correo ya está registrado por otra cuenta.',
            'telefono.digits' => 'El teléfono debe contener exactamente 10 dígitos.',
            'direccion.min' => 'La dirección debe tener al menos 5 caracteres.',
            'direccion.max' => 'La dirección no puede superar 100 caracteres.',
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
