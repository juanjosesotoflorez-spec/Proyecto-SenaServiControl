<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BusquedaFiltroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'q'          => $this->q ? strip_tags(trim($this->q)) : null,
            'documento'  => $this->documento ? preg_replace('/[^a-zA-Z0-9\-]/', '', trim($this->documento)) : null,
            'placa'      => $this->placa ? strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', trim($this->placa))) : null,
            'estado'     => $this->estado ? strip_tags(trim($this->estado)) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'q'           => ['nullable', 'string', 'max:100'],
            'documento'   => ['nullable', 'string', 'max:30'],
            'nombre'      => ['nullable', 'string', 'max:100'],
            'telefono'    => ['nullable', 'string', 'max:20'],
            'placa'       => ['nullable', 'string', 'max:10'],
            'marca'       => ['nullable', 'string', 'max:50'],
            'modelo'      => ['nullable', 'string', 'max:50'],
            'numero'      => ['nullable', 'string', 'max:30'],
            'estado'      => ['nullable', 'string', 'max:30'],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
        ];
    }
}