<?php

namespace App\Enums;

enum EstadoServicioEnum: string
{
    case Pendiente = 'Pendiente';
    case EnProceso = 'En proceso';
    case Finalizado = 'Finalizado';
    case Entregado = 'Entregado';

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pendiente => 'bg-warning text-dark',
            self::EnProceso => 'bg-info text-dark',
            self::Finalizado, self::Entregado => 'bg-success',
        };
    }
}
