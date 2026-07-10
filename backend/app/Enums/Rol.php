<?php

namespace App\Enums;

enum Rol: string
{
    case ADMIN = 'admin';
    case CIUDADANO = 'ciudadano';
    case EMPRESA = 'empresa';
}