<?php

namespace Database\Factories;

use App\Models\Ciudadano;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ciudadano>
 */
class CiudadanoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'usuario_id' => User::factory()->ciudadano(),
            'ci' => fake()->unique()->numerify('########'),
            'zona' => fake()->randomElement(['Zona Sur', 'Valle Hermoso', 'Calacala', 'Cala Cala']),
        ];
    }
}