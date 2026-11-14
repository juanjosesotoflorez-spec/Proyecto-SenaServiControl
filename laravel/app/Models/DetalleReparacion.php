<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleReparacion extends LegacyModel
{
    protected $table = 'detalle_reparacion';

    protected $primaryKey = 'id_detalle';

    protected $fillable = ['id_reparacion', 'id_repuesto', 'cantidad', 'subtotal'];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'subtotal' => 'decimal:2',
        ];
    }

    public function reparacion(): BelongsTo
    {
        return $this->belongsTo(Reparacion::class, 'id_reparacion', 'id_reparacion');
    }

    public function repuesto(): BelongsTo
    {
        return $this->belongsTo(Repuesto::class, 'id_repuesto', 'id_repuesto');
    }
}
