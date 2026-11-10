<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tecnico extends LegacyModel
{
    protected $table = 'tecnico';

    protected $primaryKey = 'id_tecnico';

    protected $fillable = ['nombre', 'apellido', 'documento', 'telefono', 'especialidad', 'experiencia', 'id_usuario'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class, 'id_tecnico', 'id_tecnico');
    }

    public function seguimientos(): HasMany
    {
        return $this->hasMany(Seguimiento::class, 'id_tecnico', 'id_tecnico');
    }
}
