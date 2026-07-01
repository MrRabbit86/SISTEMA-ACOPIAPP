<?php

namespace App\Http\Resources;

use App\Enums\EstadoTransaccion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransaccionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'peso_real_kg' => (float) $this->peso_real_kg,
            'precio_acordado_kg' => (float) $this->precio_acordado_kg,
            'monto_total' => (float) $this->monto_total,
            'estado' => $this->estado instanceof EstadoTransaccion ? $this->estado->value : $this->estado,
            'fecha_transaccion' => $this->fecha_transaccion?->toISOString(),
            'oferta' => $this->when($this->relationLoaded('oferta'), function () {
                return $this->oferta ? [
                    'id' => $this->oferta->id,
                    'material' => $this->oferta->categoria?->nombre,
                    'cantidad_estimada_kg' => $this->oferta->cantidad_estimada_kg,
                    'estado' => $this->oferta->estado?->value,
                    'ciudadano' => $this->oferta->ciudadano?->usuario?->name,
                    'zona' => $this->oferta->ciudadano?->zona,
                ] : null;
            }),
            'empresa' => $this->when($this->relationLoaded('empresa'), function () {
                return $this->empresa ? [
                    'id' => $this->empresa->id,
                    'name' => $this->empresa->name,
                    'razon_social' => $this->empresa->empresa?->razon_social,
                ] : null;
            }),
        ];
    }
}