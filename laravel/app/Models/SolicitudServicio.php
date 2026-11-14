<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudServicio extends LegacyModel
{
    protected $table = 'solicitud_servicio';

    protected $primaryKey = 'id_solicitud';

    protected $fillable = [
        'id_vehiculo',
        'tipo_servicio',
        'descripcion',
        'fecha_solicitada',
        'estado',
        'fecha_solicitud',
        'id_servicio',
    ];

    protected function casts(): array
    {
        return [
            'fecha_solicitada' => 'date',
            'fecha_solicitud' => 'datetime',
        ];
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'id_vehiculo', 'id_vehiculo');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'id_servicio', 'id_servicio');
    }
}
