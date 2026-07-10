<?php

namespace Database\Factories;

use App\Enums\EstadoOferta;
use App\Enums\ModalidadEntrega;
use App\Models\CategoriaMaterial;
use App\Models\Ciudadano;
use App\Models\Oferta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Oferta>
 */
class OfertaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ciudadano_id' => Ciudadano::factory(),
            'categoria_id' => CategoriaMaterial::factory(),
            'cantidad_estimada_kg' => fake()->randomFloat(2, 1, 500),
            'modalidad_entrega' => ModalidadEntrega::ENTREGA_PUNTO_VERDE->value,
            'latitud' => fake()->latitude(-17.45, -17.35),
            'longitud' => fake()->longitude(-66.25, -66.10),
            'estado' => EstadoOferta::PENDIENTE->value,
        ];
    }

    public function completada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => EstadoOferta::COMPLETADA->value,
        ]);
    }

    public function cancelada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => EstadoOferta::CANCELADA->value,
        ]);
    }
}