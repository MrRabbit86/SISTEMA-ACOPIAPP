<?php

namespace App\Enums;

enum EstadoOferta: string
{
    case PENDIENTE = 'pendiente';
    case EN_PROCESO = 'en_proceso';
    case COMPLETADA = 'completada';
    case CANCELADA = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::PENDIENTE => 'Disponible',
            self::EN_PROCESO => 'En proceso',
            self::COMPLETADA => 'Completada',
            self::CANCELADA => 'Cancelada',
        };
    }
}