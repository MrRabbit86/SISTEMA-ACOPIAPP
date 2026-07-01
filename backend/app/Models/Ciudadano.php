<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['usuario_id', 'ci', 'zona', 'puntos'])]
class Ciudadano extends Model
{
    /** @use HasFactory<\Database\Factories\CiudadanoFactory> */
    use HasFactory;
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function ofertas(): HasMany
    {
        return $this->hasMany(Oferta::class);
    }

    protected function casts(): array
    {
        return [
            'puntos' => 'integer',
        ];
    }
}