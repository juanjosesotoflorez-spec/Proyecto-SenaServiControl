<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialActividadUsuario extends LegacyModel
{
    protected $table = 'historial_actividad_usuario';

    protected $primaryKey = 'id_actividad';

    protected $fillable = ['id_usuario', 'id_servicio', 'entidad', 'id_registro', 'accion', 'descripcion', 'fecha_hora'];

    protected function casts(): array
    {
        return ['fecha_hora' => 'datetime'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'id_servicio', 'id_servicio');
    }
}
