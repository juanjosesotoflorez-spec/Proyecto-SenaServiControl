<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Administrador extends LegacyModel
{
    protected $table = 'administrador';

    protected $primaryKey = 'id_administrador';

    protected $fillable = ['nombre', 'apellido', 'documento', 'telefono', 'id_usuario'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
