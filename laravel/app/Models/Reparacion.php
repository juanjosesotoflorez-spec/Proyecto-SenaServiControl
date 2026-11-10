<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reparacion extends LegacyModel
{
    protected $table = 'reparacion';

    protected $primaryKey = 'id_reparacion';

    protected $fillable = [
        'problema_reportado',
        'dano_detectado',
        'presupuesto',
        'solucion',
        'observaciones',
        'estado',
        'estado_presupuesto',
        'fecha_respuesta_presupuesto',
        'id_servicio',
    ];

    protected function casts(): array
    {
        return [
            'presupuesto' => 'decimal:2',
            'fecha_respuesta_presupuesto' => 'datetime',
        ];
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'id_servicio', 'id_servicio');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleReparacion::class, 'id_reparacion', 'id_reparacion');
    }
}
