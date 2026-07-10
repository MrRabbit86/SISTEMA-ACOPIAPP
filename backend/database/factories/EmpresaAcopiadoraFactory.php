<?php

namespace Database\Factories;

use App\Models\EmpresaAcopiadora;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmpresaAcopiadora>
 */
class EmpresaAcopiadoraFactory extends Factory
{
    public function definition(): array
    {
        return [
            'usuario_id' => User::factory()->empresa(),
            'nit' => fake()->unique()->numerify('###########'),
            'razon_social' => fake()->company(),
            'direccion' => fake()->address(),
        ];
    }
}