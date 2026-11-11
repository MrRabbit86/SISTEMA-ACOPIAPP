<?php

namespace App\Http\Resources;

use App\Enums\EstadoOferta;
use App\Enums\ModalidadEntrega;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class OfertaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cantidad_estimada_kg' => $this->cantidad_estimada_kg,
            'descripcion' => $this->descripcion,
            'latitud' => $this->latitud,
            'longitud' => $this->longitud,
            'estado' => $this->estado instanceof EstadoOferta ? $this->estado->value : $this->estado,
            'estado_label' => $this->estado instanceof EstadoOferta ? $this->estado->label() : $this->estado,
            'modalidad_entrega' => $this->modalidad_entrega instanceof ModalidadEntrega ? $this->modalidad_entrega->value : $this->modalidad_entrega,
            'modalidad_label' => $this->modalidad_entrega instanceof ModalidadEntrega ? $this->modalidad_entrega->label() : null,
            'foto_url' => $this->foto_url ? Storage::disk('public')->url($this->foto_url) : null,
            'fecha_publicacion' => $this->fecha_publicacion?->toISOString(),
            'reservada_hasta' => $this->reservada_hasta?->toISOString(),
            'codigo_confirmacion' => $this->when(
                $request->user() && $this->ciudadano && $this->ciudadano->usuario_id === $request->user()->id,
                fn () => $this->codigo_confirmacion,
            ),
            'empresa_reservada' => $this->when($this->relationLoaded('empresaReservada'), function () {
                return $this->empresaReservada ? [
                    'id' => $this->empresaReservada->id,
                    'nombre' => $this->empresaReservada->name,
                    'telefono' => $this->empresaReservada->telefono,
                ] : null;
            }),
            'ciudadano' => $this->when($this->relationLoaded('ciudadano'), function () {
                return $this->ciudadano ? [
                    'id' => $this->ciudadano->id,
                    'nombre' => $this->ciudadano->usuario?->name,
                    'zona' => $this->ciudadano->zona,
                ] : null;
            }),
            'categoria' => $this->when($this->relationLoaded('categoria'), function () {
                return $this->categoria ? [
                    'id' => $this->categoria->id,
                    'nombre' => $this->categoria->nombre,
                    'precio_referencia_kg' => $this->categoria->precio_referencia_kg,
                ] : null;
            }),
        ];
    }
}