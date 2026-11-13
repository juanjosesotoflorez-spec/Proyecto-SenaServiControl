<?php

namespace App\Http\Requests;

use App\Enums\RolUsuario;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehiculoRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('placa')) {
            $this->merge(['placa' => strtoupper((string) $this->input('placa'))]);
        }
    }

    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof Usuario
            && in_array($user->rol, [RolUsuario::Administrador, RolUsuario::Recepcionista, RolUsuario::Cliente], true);
    }

    public function rules(): array
    {
        $staffCanSelectClient = in_array($this->user()?->rol, [RolUsuario::Administrador, RolUsuario::Recepcionista], true);

        return [
            'placa' => ['required', 'string', 'size:6', 'regex:/^[A-Z0-9]{6}$/', Rule::unique('vehiculo', 'placa')],
            'marca' => ['nullable', 'string', 'min:2', 'max:40'],
            'modelo' => ['nullable', 'string', 'min:2', 'max:40'],
            'color' => ['nullable', 'string', 'max:30'],
            'anio' => ['nullable', 'regex:/^[0-9]+$/', 'integer', 'between:1950,'.((int) date('Y') + 1)],
            'kilometraje' => ['nullable', 'regex:/^[0-9]+$/', 'integer', 'min:0', 'max:4294967295'],
            'id_cliente' => [$staffCanSelectClient ? 'required' : 'prohibited', 'integer', 'exists:cliente,id_cliente'],
        ];
    }

    public function messages(): array
    {
        return [
            'placa.required' => 'La placa es obligatoria.',
            'placa.string' => 'La placa debe ser un texto válido.',
            'placa.size' => 'La placa debe tener exactamente 6 caracteres.',
            'placa.regex' => 'La placa debe contener exactamente 6 letras mayúsculas o números.',
            'placa.unique' => 'Ya existe un vehículo registrado con esta placa.',
            'marca.string' => 'La marca debe ser un texto válido.',
            'marca.min' => 'La marca debe tener al menos 2 caracteres.',
            'marca.max' => 'La marca no puede superar 40 caracteres.',
            'modelo.string' => 'El modelo debe ser un texto válido.',
            'modelo.min' => 'El modelo debe tener al menos 2 caracteres.',
            'modelo.max' => 'El modelo no puede superar 40 caracteres.',
            'color.string' => 'El color debe ser un texto válido.',
            'color.max' => 'El color no puede superar 30 caracteres.',
            'anio.integer' => 'El año debe contener solo números enteros.',
            'anio.regex' => 'El año debe contener solo números de 0 a 9.',
            'anio.between' => 'El año debe estar entre 1950 y '.((int) date('Y') + 1).'.',
            'kilometraje.integer' => 'El kilometraje debe contener solo números enteros.',
            'kilometraje.regex' => 'El kilometraje debe contener solo números de 0 a 9.',
            'kilometraje.min' => 'El kilometraje no puede ser negativo.',
            'kilometraje.max' => 'El kilometraje supera el máximo permitido.',
            'id_cliente.required' => 'Selecciona el cliente propietario del vehículo.',
            'id_cliente.integer' => 'El cliente seleccionado no es válido.',
            'id_cliente.exists' => 'El cliente seleccionado no existe.',
            'id_cliente.prohibited' => 'No puedes seleccionar un cliente manualmente desde esta cuenta.',
        ];
    }
}
