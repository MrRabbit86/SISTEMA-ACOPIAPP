<?php

namespace App\Models;

use App\Enums\EstadoTransaccion;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

#[Fillable(['oferta_id', 'empresa_id', 'peso_real_kg', 'precio_acordado_kg', 'monto_total', 'puntos_otorgados', 'estado', 'fecha_transaccion'])]
class Transaccion extends Model
{
    /** @use HasFactory<\Database\Factories\TransaccionFactory> */
    use HasFactory;

    public const PUNTOS_POR_KG = 10;

    protected $table = 'transacciones';

    protected static function booted(): void
    {
        static::created(function (Transaccion $transaccion): void {
            if ($transaccion->puntos_otorgados !== null) {
                return;
            }

            $ciudadano = $transaccion->oferta?->ciudadano;

            if (! $ciudadano) {
                return;
            }

            $puntos = (int) floor((float) $transaccion->peso_real_kg * self::PUNTOS_POR_KG);

            $transaccion->forceFill(['puntos_otorgados' => $puntos])->save();
            $ciudadano->increment('puntos', $puntos);
        });
    }

    public function oferta(): BelongsTo
    {
        return $this->belongsTo(Oferta::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'empresa_id');
    }

    public function comprobante(): HasOne
    {
        return $this->hasOne(Comprobante::class);
    }

    public function esCompletable(): bool
    {
        return $this->estado === EstadoTransaccion::REGISTRADA->value;
    }

    protected function casts(): array
    {
        return [
            'peso_real_kg' => 'decimal:2',
            'precio_acordado_kg' => 'decimal:2',
            'monto_total' => 'decimal:2',
            'fecha_transaccion' => 'datetime',
            'estado' => EstadoTransaccion::class,
        ];
    }
}