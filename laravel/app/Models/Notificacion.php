<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notificacion extends LegacyModel
{
    protected $table = 'notificacion';

    protected $primaryKey = 'id_notificacion';

    protected $fillable = ['mensaje', 'fecha_envio', 'estado', 'id_cliente', 'id_servicio'];

    protected function casts(): array
    {
        return ['fecha_envio' => 'datetime'];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'id_servicio', 'id_servicio');
    }

    public function confirmaciones(): HasMany
    {
        return $this->hasMany(ConfirmacionNotificacion::class, 'id_notificacion', 'id_notificacion');
    }
}
