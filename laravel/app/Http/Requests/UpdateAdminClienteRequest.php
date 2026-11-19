<?php

namespace App\Http\Requests;

use App\Enums\RolUsuario;
use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Usuario
            && $this->user()->rol === RolUsuario::Administrador;
    }

    public function rules(): array
    {
        $cliente = $this->route('cliente');
        abort_unless($cliente instanceof Cliente, 404);

        $usuarioCorreoRule = Rule::unique('usuario', 'correo');
        if ($cliente->id_usuario) {
            $usuarioCorreoRule->ignore($cliente->id_usuario, 'id_usuario');
        }

        return [
            'nombre' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[\pL ]+$/u'],
            'apellido' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[\pL ]+$/u'],
            'documento' => ['required', 'string', 'digits_between:6,12', Rule::unique('cliente', 'documento')->ignore($cliente->getKey(), 'id_cliente')],
            'telefono' => ['required', 'string', 'digits:10'],
            'direccion' => ['required', 'string', 'min:5', 'max:100'],
            'correo' => [
                'required',
                'email:rfc',
                'max:100',
                Rule::unique('cliente', 'correo')->ignore($cliente->getKey(), 'id_cliente'),
                $usuarioCorreoRule,
            ],
        ];
    }
}
