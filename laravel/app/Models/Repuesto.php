<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Repuesto extends LegacyModel
{
    protected $table = 'repuesto';

    protected $primaryKey = 'id_repuesto';

    protected $fillable = ['nombre', 'descripcion', 'precio', 'stock'];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'stock' => 'integer',
        ];
    }

    public function detallesReparacion(): HasMany
    {
        return $this->hasMany(DetalleReparacion::class, 'id_repuesto', 'id_repuesto');
    }
}
