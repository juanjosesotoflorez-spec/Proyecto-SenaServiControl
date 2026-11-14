<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Seguimiento extends LegacyModel
{
    protected $table = 'seguimiento';

    protected $primaryKey = 'id_seguimiento';

    protected $fillable = ['fecha', 'fecha_hora', 'estado', 'comentario', 'id_servicio', 'id_tecnico', 'aprendiz'];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'fecha_hora' => 'datetime',
        ];
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'id_servicio', 'id_servicio');
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(Tecnico::class, 'id_tecnico', 'id_tecnico');
    }
}
