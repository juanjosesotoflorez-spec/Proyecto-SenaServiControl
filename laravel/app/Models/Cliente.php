<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends LegacyModel
{
    protected $table = 'cliente';

    protected $primaryKey = 'id_cliente';

    protected $fillable = ['nombre', 'apellido', 'documento', 'telefono', 'direccion', 'correo', 'id_usuario'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function vehiculos(): HasMany
    {
        return $this->hasMany(Vehiculo::class, 'id_cliente', 'id_cliente');
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'id_cliente', 'id_cliente');
    }
}
