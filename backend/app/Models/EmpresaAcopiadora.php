<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['usuario_id', 'nit', 'razon_social', 'direccion'])]
class EmpresaAcopiadora extends Model
{
    protected $table = 'empresas_acopiadoras';

    /** @use HasFactory<\Database\Factories\EmpresaAcopiadoraFactory> */
    use HasFactory;
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function transacciones(): HasMany
    {
        return $this->hasMany(Transaccion::class, 'empresa_id');
    }
}