<?php

namespace App\Models;

class VistaInventarioRepuestos extends LegacyModel
{
    protected $table = 'vista_inventario_repuestos';

    protected $primaryKey = 'id_repuesto';

    public $incrementing = false;
}
