<?php

namespace App\Enums;

enum ModalidadEntrega: string
{
    case RECOJO_DOMICILIO = 'recojo_domicilio';
    case ENTREGA_PUNTO_VERDE = 'entrega_punto_verde';

    public function label(): string
    {
        return match ($this) {
            self::RECOJO_DOMICILIO => 'Recojo a domicilio',
            self::ENTREGA_PUNTO_VERDE => 'Entrego en el punto verde',
        };
    }
}