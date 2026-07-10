<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['transaccion_id', 'numero_comprobante', 'pdf_url', 'fecha_emision'])]
class Comprobante extends Model
{
    public function transaccion(): BelongsTo
    {
        return $this->belongsTo(Transaccion::class);
    }

    protected function casts(): array
    {
        return [
            'fecha_emision' => 'datetime',
        ];
    }
}