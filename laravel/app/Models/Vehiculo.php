<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehiculo extends LegacyModel
{
    protected $table = 'vehiculo';

    protected $primaryKey = 'id_vehiculo';

    protected $fillable = ['placa', 'marca', 'modelo', 'color', 'anio', 'kilometraje', 'id_cliente'];

    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'kilometraje' => 'integer',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class, 'id_vehiculo', 'id_vehiculo');
    }

    public function solicitudesServicio(): HasMany
    {
        return $this->hasMany(SolicitudServicio::class, 'id_vehiculo', 'id_vehiculo');
    }
}
