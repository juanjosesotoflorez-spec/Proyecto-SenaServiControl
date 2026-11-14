<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntregaVehiculo extends LegacyModel
{
    protected $table = 'entrega_vehiculo';

    protected $primaryKey = 'id_entrega';

    protected $fillable = [
        'id_servicio',
        'id_recepcionista',
        'fecha_registro',
        'fecha_salida',
        'estado_confirmacion_cliente',
        'fecha_confirmacion_cliente',
    ];

    protected function casts(): array
    {
        return [
            'fecha_registro' => 'datetime',
            'fecha_salida' => 'datetime',
            'fecha_confirmacion_cliente' => 'datetime',
        ];
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'id_servicio', 'id_servicio');
    }

    public function recepcionista(): BelongsTo
    {
        return $this->belongsTo(Recepcionista::class, 'id_recepcionista', 'id_recepcionista');
    }
}
