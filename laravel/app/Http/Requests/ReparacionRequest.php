<?php

namespace App\Http\Requests;

use App\Enums\RolUsuario;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;

class ReparacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $usuario = $this->user();

        return $usuario instanceof Usuario
            && in_array($usuario->rol, [RolUsuario::Tecnico, RolUsuario::Administrador], true);
    }

    public function rules(): array
    {
        return [
            'problema_reportado' => ['required', 'string', 'min:10', 'max:5000'],
            'dano_detectado' => ['required', 'string', 'min:10', 'max:5000'],
            'solucion' => ['required', 'string', 'min:10', 'max:5000'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
            'mano_obra' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'repuestos' => ['nullable', 'array'],
            'repuestos.*' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'problema_reportado.required' => 'Describe el problema reportado.',
            'problema_reportado.min' => 'El problema reportado debe tener al menos 10 caracteres.',
            'dano_detectado.required' => 'Describe el daño detectado en el diagnóstico.',
            'dano_detectado.min' => 'El daño detectado debe tener al menos 10 caracteres.',
            'solucion.required' => 'Indica la solución propuesta.',
            'solucion.min' => 'La solución propuesta debe tener al menos 10 caracteres.',
            'mano_obra.required' => 'Indica el valor de la mano de obra.',
            'mano_obra.numeric' => 'La mano de obra debe ser un valor numérico.',
            'mano_obra.min' => 'La mano de obra no puede ser negativa.',
            'repuestos.*.integer' => 'La cantidad de cada repuesto debe ser un número entero.',
            'repuestos.*.min' => 'La cantidad de repuestos no puede ser negativa.',
        ];
    }
}