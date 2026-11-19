<?php

namespace App\Http\Requests;

use App\Enums\RolUsuario;
use App\Models\Usuario;
use App\Models\Vehiculo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminVehiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Usuario
            && $this->user()->rol === RolUsuario::Administrador;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('placa')) {
            $this->merge(['placa' => strtoupper((string) $this->input('placa'))]);
        }
    }

    public function rules(): array
    {
        $vehiculo = $this->route('vehiculo');
        abort_unless($vehiculo instanceof Vehiculo, 404);

        return [
            'placa' => ['required', 'string', 'size:6', 'regex:/^[A-Z0-9]{6}$/', Rule::unique('vehiculo', 'placa')->ignore($vehiculo->getKey(), 'id_vehiculo')],
            'marca' => ['required', 'string', 'min:2', 'max:40'],
            'modelo' => ['required', 'string', 'min:2', 'max:40'],
            'color' => ['required', 'string', 'max:30'],
            'anio' => ['required', 'integer', 'between:1950,'.((int) date('Y') + 1)],
            'kilometraje' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'id_cliente' => ['required', 'integer', 'exists:cliente,id_cliente'],
        ];
    }
}
