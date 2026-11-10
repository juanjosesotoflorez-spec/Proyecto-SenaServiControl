<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mantenimiento extends LegacyModel
{
    protected $table = 'mantenimiento';

    protected $primaryKey = 'id_mantenimiento';

    protected $fillable = [
        'tipo_mantenimiento',
        'observaciones',
        'fecha_programacion',
        'estado_autorizacion',
        'estado_trabajo',
        'fecha_respuesta_autorizacion',
        'id_servicio',
    ];

    protected function casts(): array
    {
        return [
            'fecha_programacion' => 'date',
            'fecha_respuesta_autorizacion' => 'datetime',
        ];
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'id_servicio', 'id_servicio');
    }
}
