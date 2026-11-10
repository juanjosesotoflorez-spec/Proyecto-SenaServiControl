<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Incidencia extends LegacyModel
{
    protected $table = 'incidencia';

    protected $primaryKey = 'id_incidencia';

    protected $fillable = [
        'id_usuario_reporta',
        'id_usuario_atendio',
        'id_servicio',
        'descripcion',
        'estado',
        'nota_atencion',
        'fecha_registro',
        'fecha_actualizacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_registro' => 'datetime',
            'fecha_actualizacion' => 'datetime',
        ];
    }

    public function usuarioReporta(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_reporta', 'id_usuario');
    }

    public function usuarioAtendio(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_atendio', 'id_usuario');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'id_servicio', 'id_servicio');
    }
}
