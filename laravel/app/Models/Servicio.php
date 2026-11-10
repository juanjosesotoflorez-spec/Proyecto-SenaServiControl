<?php

namespace App\Models;

use App\Enums\EstadoServicioEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Servicio extends LegacyModel
{
    protected $table = 'servicio';

    protected $primaryKey = 'id_servicio';

    protected $fillable = [
        'tipo_servicio',
        'descripcion',
        'fecha_ingreso',
        'fecha_cita',
        'fecha_entrega',
        'fecha_entrega_estimada',
        'fecha_entrega_real',
        'estado',
        'costo',
        'id_vehiculo',
        'id_tecnico',
        'id_recepcionista',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'date',
            'fecha_cita' => 'date',
            'fecha_entrega' => 'date',
            'fecha_entrega_estimada' => 'date',
            'fecha_entrega_real' => 'date',
            'costo' => 'decimal:2',
            'estado' => EstadoServicioEnum::class,
        ];
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'id_vehiculo', 'id_vehiculo');
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(Tecnico::class, 'id_tecnico', 'id_tecnico');
    }

    public function recepcionista(): BelongsTo
    {
        return $this->belongsTo(Recepcionista::class, 'id_recepcionista', 'id_recepcionista');
    }

    public function mantenimientos(): HasMany
    {
        return $this->hasMany(Mantenimiento::class, 'id_servicio', 'id_servicio');
    }

    public function reparaciones(): HasMany
    {
        return $this->hasMany(Reparacion::class, 'id_servicio', 'id_servicio');
    }

    public function seguimientos(): HasMany
    {
        return $this->hasMany(Seguimiento::class, 'id_servicio', 'id_servicio');
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'id_servicio', 'id_servicio');
    }

    public function solicitudes(): HasMany
    {
        return $this->hasMany(SolicitudServicio::class, 'id_servicio', 'id_servicio');
    }

    public function asignacionesTecnico(): HasMany
    {
        return $this->hasMany(HistorialAsignacionTecnico::class, 'id_servicio', 'id_servicio');
    }

    public function asignacionActual(): HasOne
    {
        return $this->hasOne(HistorialAsignacionTecnico::class, 'id_servicio', 'id_servicio')
            ->latestOfMany('id_asignacion');
    }

    public function entregas(): HasMany
    {
        return $this->hasMany(EntregaVehiculo::class, 'id_servicio', 'id_servicio');
    }
}
