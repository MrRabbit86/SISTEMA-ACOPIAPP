<?php

namespace App\Models;

use App\Enums\EstadoOferta;
use App\Enums\ModalidadEntrega;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['ciudadano_id', 'categoria_id', 'cantidad_estimada_kg', 'descripcion', 'modalidad_entrega', 'foto_url', 'latitud', 'longitud', 'estado', 'fecha_publicacion'])]
class Oferta extends Model
{
    /** @use HasFactory<\Database\Factories\OfertaFactory> */
    use HasFactory;

    public static function guardarFoto(?UploadedFile $foto): ?string
    {
        if (! $foto) {
            return null;
        }

        $extension = $foto->guessExtension() ?: $foto->getClientOriginalExtension() ?: 'jpg';
        $nombre = 'ofertas/'.Str::uuid().'.'.$extension;
        Storage::disk('public')->putFileAs('ofertas', $foto, basename($nombre));

        return $nombre;
    }

    public function ciudadano(): BelongsTo
    {
        return $this->belongsTo(Ciudadano::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaMaterial::class, 'categoria_id');
    }

    public function transaccion(): HasOne
    {
        return $this->hasOne(Transaccion::class);
    }

    public function obtenerPrecioReferencia(): float
    {
        return (float) ($this->categoria?->precio_referencia_kg ?? 0);
    }

    public static function existeDuplicada(int $ciudadanoId, int $categoriaId): bool
    {
        return static::query()
            ->where('ciudadano_id', $ciudadanoId)
            ->where('categoria_id', $categoriaId)
            ->whereIn('estado', [EstadoOferta::PENDIENTE->value, EstadoOferta::EN_PROCESO->value])
            ->where('fecha_publicacion', '>', now()->subHours(24))
            ->exists();
    }

    public function estaDisponible(): bool
    {
        return $this->estado === EstadoOferta::PENDIENTE->value;
    }

    public function modalidadLabel(): ?string
    {
        return $this->modalidad_entrega instanceof ModalidadEntrega
            ? $this->modalidad_entrega->label()
            : null;
    }

    protected function casts(): array
    {
        return [
            'cantidad_estimada_kg' => 'decimal:2',
            'latitud' => 'decimal:7',
            'longitud' => 'decimal:7',
            'fecha_publicacion' => 'datetime',
            'estado' => EstadoOferta::class,
            'modalidad_entrega' => ModalidadEntrega::class,
        ];
    }
}