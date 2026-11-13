<?php

namespace App\Http\Requests;

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
            'usuario' => ['required', 'string', 'min:3', 'max:50', Rule::unique('usuario', 'usuario')],
            'correo' => ['required', 'email:rfc', 'max:100', Rule::unique('usuario', 'correo')],
            'contrasena' => [
                'required',
                'string',
                'min:8',
                'max:255',
                'regex:/\p{Lu}/u',
                'regex:/\p{N}.*\p{N}/us',
                'regex:/[^\p{L}\p{N}\s]/u',
                'confirmed',
            ],
            'nombre' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[\pL ]+$/u'],
            'apellido' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[\pL ]+$/u'],
            'documento' => ['required', 'string', 'digits_between:6,12', Rule::unique('cliente', 'documento')],
            'telefono' => ['nullable', 'string', 'digits:10'],
            'direccion' => ['nullable', 'string', 'min:5', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'usuario.required' => 'El nombre de usuario es obligatorio.',
            'usuario.string' => 'El nombre de usuario debe ser un texto válido.',
            'usuario.min' => 'El nombre de usuario debe tener al menos 3 caracteres.',
            'usuario.max' => 'El nombre de usuario no puede superar 50 caracteres.',
            'usuario.unique' => 'Este nombre de usuario ya está registrado.',
            'correo.required' => 'El correo electrónico es obligatorio.',
            'correo.string' => 'El correo debe ser un texto válido.',
            'correo.email' => 'Ingresa un correo electrónico válido.',
            'correo.max' => 'El correo no puede superar 100 caracteres.',
            'correo.unique' => 'Este correo electrónico ya está registrado.',
            'contrasena.required' => 'La contraseña es obligatoria.',
            'contrasena.string' => 'La contraseña debe ser un texto válido.',
            'contrasena.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'contrasena.max' => 'La contraseña no puede superar 255 caracteres.',
            'contrasena.regex' => 'La contraseña debe incluir al menos una mayúscula, dos números y un carácter especial.',
            'contrasena.confirmed' => 'La confirmación de contraseña no coincide.',
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
            'telefono.string' => 'El teléfono debe contener solo números.',
            'telefono.digits' => 'El teléfono debe contener exactamente 10 dígitos.',
            'direccion.string' => 'La dirección debe ser un texto válido.',
            'direccion.min' => 'La dirección debe tener al menos 5 caracteres.',
            'direccion.max' => 'La dirección no puede superar 100 caracteres.',
        ];
    }
}
