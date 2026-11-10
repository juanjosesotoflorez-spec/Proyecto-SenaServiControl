<?php

namespace App\Http\Requests;

use App\Enums\RolUsuario;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;

class AcceptServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Usuario
            && $this->user()->rol === RolUsuario::Tecnico;
    }

    public function rules(): array
    {
        return [];
    }
}