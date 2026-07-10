<?php

namespace App\Http\Resources;

use App\Enums\EstadoUsuario;
use App\Enums\Rol;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'rol' => $this->rol instanceof Rol ? $this->rol->value : $this->rol,
            'telefono' => $this->telefono,
            'direccion' => $this->direccion,
            'estado' => $this->estado instanceof EstadoUsuario ? $this->estado->value : $this->estado,
            'ci' => $this->when($this->relationLoaded('ciudadano'), fn () => $this->ciudadano?->ci),
            'zona' => $this->when($this->relationLoaded('ciudadano'), fn () => $this->ciudadano?->zona),
            'puntos' => $this->when($this->relationLoaded('ciudadano'), fn () => $this->ciudadano?->puntos ?? 0),
            'nit' => $this->when($this->relationLoaded('empresa'), fn () => $this->empresa?->nit),
            'razon_social' => $this->when($this->relationLoaded('empresa'), fn () => $this->empresa?->razon_social),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}