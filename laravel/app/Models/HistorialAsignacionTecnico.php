<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialAsignacionTecnico extends LegacyModel
{
    protected $table = 'historial_asignacion_tecnico';

    protected $primaryKey = 'id_asignacion';

    protected $fillable = [
        'id_servicio',
        'id_tecnico_anterior',
        'id_tecnico_nuevo',
        'id_recepcionista',
        'id_administrador',
        'fecha_asignacion',
        'motivo',
        'estado_aceptacion',
        'fecha_aceptacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_asignacion' => 'datetime',
            'fecha_aceptacion' => 'datetime',
        ];
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'id_servicio', 'id_servicio');
    }

    public function tecnicoAnterior(): BelongsTo
    {
        return $this->belongsTo(Tecnico::class, 'id_tecnico_anterior', 'id_tecnico');
    }

    public function tecnicoNuevo(): BelongsTo
    {
        return $this->belongsTo(Tecnico::class, 'id_tecnico_nuevo', 'id_tecnico');
    }
}
