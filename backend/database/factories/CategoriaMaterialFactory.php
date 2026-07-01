<?php

namespace Database\Factories;

use App\Models\CategoriaMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoriaMaterial>
 */
class CategoriaMaterialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->randomElement(['Plástico PET', 'Cartón', 'Vidrio', 'Papel', 'Metales']),
            'descripcion' => fake()->sentence(),
            'precio_referencia_kg' => fake()->randomFloat(2, 0.5, 5),
        ];
    }
}