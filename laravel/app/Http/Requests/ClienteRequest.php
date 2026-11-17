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
            'nombre' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[\pL ]+$/u'],
            'apellido' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[\pL ]+$/u'],
            'documento' => ['required', 'string', 'digits_between:6,12', Rule::unique('cliente', 'documento')],
            'telefono' => ['required', 'string', 'digits:10'],
            'direccion' => ['required', 'string', 'min:5', 'max:100'],
            'correo' => [
                'required',
                'email:rfc',
                'max:100',
                Rule::unique('cliente', 'correo'),
                Rule::unique('usuario', 'correo'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.string' => 'El nombre debe ser un texto válido.',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.max' => 'El nombre no puede superar 50 caracteres.',
            'nombre.regex' => 'Solo se permiten letras, tildes, ñ y espacios.',
            'apellido.required' => 'El apellido es obligatorio.',
            'apellido.string' => 'El apellido debe ser un texto válido.',
            'apellido.min' => 'El apellido debe tener al menos 2 caracteres.',
            'apellido.max' => 'El apellido no puede superar 50 caracteres.',
            'apellido.regex' => 'Solo se permiten letras, tildes, ñ y espacios.',
            'documento.required' => 'El documento es obligatorio.',
            'documento.string' => 'El documento debe contener solo números.',
            'documento.digits_between' => 'El documento debe contener entre 6 y 12 dígitos, sin espacios ni signos.',
            'documento.unique' => 'Ya existe un cliente con este documento.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.string' => 'El teléfono debe contener solo números.',
            'telefono.digits' => 'El teléfono debe contener exactamente 10 dígitos.',
            'direccion.required' => 'La dirección es obligatoria.',
            'direccion.string' => 'La dirección debe ser un texto válido.',
            'direccion.min' => 'La dirección debe tener al menos 5 caracteres.',
            'direccion.max' => 'La dirección no puede superar 100 caracteres.',
            'correo.required' => 'El correo electrónico es obligatorio.',
            'correo.email' => 'Ingresa un correo electrónico válido.',
            'correo.string' => 'El correo debe ser un texto válido.',
            'correo.max' => 'El correo no puede superar 100 caracteres.',
            'correo.unique' => 'Este correo electrónico ya está registrado.',
        ];
    }
}
