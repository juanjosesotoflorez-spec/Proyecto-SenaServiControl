<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfirmacionNotificacion extends LegacyModel
{
    protected $table = 'confirmacion_notificacion';

    protected $primaryKey = 'id_confirmacion';

    protected $fillable = ['id_notificacion', 'fecha_confirmacion'];

    protected function casts(): array
    {
        return ['fecha_confirmacion' => 'datetime'];
    }

    public function notificacion(): BelongsTo
    {
        return $this->belongsTo(Notificacion::class, 'id_notificacion', 'id_notificacion');
    }
}
