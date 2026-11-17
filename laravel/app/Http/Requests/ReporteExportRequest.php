<?php

namespace App\Http\Requests;

use App\Enums\EstadoServicioEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReporteExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'tipo_reporte' => ['required', 'in:operativo,productividad,vehiculos'],
            'formato' => ['required', 'in:pdf,csv,excel'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'tecnico_id' => ['nullable', 'integer', 'exists:tecnico,id_tecnico'],
            'estado' => ['nullable', Rule::enum(EstadoServicioEnum::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_fin.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
        ];
    }
}
