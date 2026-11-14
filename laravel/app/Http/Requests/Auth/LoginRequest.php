<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'correo' => ['required', 'email:rfc', 'max:100'],
            'contrasena' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'correo.required' => 'El correo electrónico es obligatorio.',
            'correo.string' => 'El correo debe ser un texto válido.',
            'correo.email' => 'Ingresa un correo electrónico válido.',
            'correo.max' => 'El correo no puede superar 100 caracteres.',
            'contrasena.required' => 'La contraseña es obligatoria.',
            'contrasena.string' => 'La contraseña debe ser un texto válido.',
            'contrasena.max' => 'La contraseña no puede superar 255 caracteres.',
        ];
    }
}
