<?php

namespace App\Http\Requests;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Usuario
            && $this->user()->rol === RolUsuario::Administrador;
    }

    public function rules(): array
    {
        $usuario = $this->route('usuario');
        abort_unless($usuario instanceof Usuario, 404);

        return [
            'usuario' => ['required', 'string', 'min:3', 'max:50', Rule::unique('usuario', 'usuario')->ignore($usuario->getKey(), 'id_usuario')],
            'correo' => ['required', 'email:rfc', 'max:100', Rule::unique('usuario', 'correo')->ignore($usuario->getKey(), 'id_usuario')],
            'rol' => ['required', Rule::enum(RolUsuario::class)],
            'estado' => ['required', Rule::enum(EstadoUsuario::class)],
        ];
    }
}
