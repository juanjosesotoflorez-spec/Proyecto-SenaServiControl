<?php

namespace App\Enums;

enum RolUsuario: string
{
    case Administrador = 'administrador';
    case Recepcionista = 'recepcionista';
    case Cliente = 'cliente';
    case Tecnico = 'tecnico';
}
