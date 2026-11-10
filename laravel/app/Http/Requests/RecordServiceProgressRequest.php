<?php

namespace App\Http\Requests;

use App\Enums\EstadoServicioEnum;
use App\Enums\RolUsuario;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordServiceProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Usuario
            && $this->user()->rol === RolUsuario::Tecnico;
    }

    public function rules(): array
    {
        return [
            'estado' => [
                'required',
                'string',
                Rule::in([EstadoServicioEnum::EnProceso->value, EstadoServicioEnum::Finalizado->value]),
            ],
            'comentario' => ['required', 'string', 'max:10000'],
            'aprendiz' => ['nullable', 'string', 'max:150'],
        ];
    }
}
