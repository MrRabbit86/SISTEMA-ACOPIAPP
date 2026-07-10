<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'descripcion', 'precio_referencia_kg'])]
class CategoriaMaterial extends Model
{
    /** @use HasFactory<\Database\Factories\CategoriaMaterialFactory> */
    use HasFactory;
    protected $table = 'categorias_material';

    public function ofertas(): HasMany
    {
        return $this->hasMany(Oferta::class, 'categoria_id');
    }

    protected function casts(): array
    {
        return [
            'precio_referencia_kg' => 'decimal:2',
        ];
    }
}