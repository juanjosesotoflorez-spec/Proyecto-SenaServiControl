<?php

namespace App\Http\Requests;

use App\Enums\RolUsuario;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClientePhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Usuario
            && $this->user()->rol === RolUsuario::Cliente;
    }

    public function rules(): array
    {
        return [
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'foto.required' => 'Selecciona una foto de perfil.',
            'foto.image' => 'El archivo debe ser una imagen válida.',
            'foto.mimes' => 'La foto debe estar en formato JPG, PNG o WebP.',
            'foto.max' => 'La foto no puede superar los 2 MB.',
        ];
    }
}
