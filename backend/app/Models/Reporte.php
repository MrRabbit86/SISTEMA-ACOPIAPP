<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

#[Fillable(['tipo', 'parametros', 'resultado', 'fecha_generacion'])]
class Reporte extends Model
{
    protected function casts(): array
    {
        return [
            'parametros' => 'array',
            'resultado' => 'array',
            'fecha_generacion' => 'datetime',
        ];
    }
}