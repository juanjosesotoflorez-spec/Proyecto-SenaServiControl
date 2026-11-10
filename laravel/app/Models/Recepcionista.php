<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recepcionista extends LegacyModel
{
    protected $table = 'recepcionista';

    protected $primaryKey = 'id_recepcionista';

    protected $fillable = ['nombre', 'apellido', 'documento', 'telefono', 'turno', 'id_usuario'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class, 'id_recepcionista', 'id_recepcionista');
    }

    public function entregas(): HasMany
    {
        return $this->hasMany(EntregaVehiculo::class, 'id_recepcionista', 'id_recepcionista');
    }
}
