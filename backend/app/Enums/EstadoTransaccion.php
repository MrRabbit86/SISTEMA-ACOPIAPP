<?php

namespace App\Enums;

enum EstadoTransaccion: string
{
    case REGISTRADA = 'registrada';
    case COMPLETADA = 'completada';

    public function label(): string
    {
        return match ($this) {
            self::REGISTRADA => 'Registrada',
            self::COMPLETADA => 'Completada',
        };
    }
}