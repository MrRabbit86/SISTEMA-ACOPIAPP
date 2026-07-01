<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'rol' => \App\Enums\Rol::CIUDADANO->value,
            'telefono' => fake()->numerify('#########'),
            'direccion' => fake()->address(),
            'estado' => \App\Enums\EstadoUsuario::APROBADO->value,
            'remember_token' => Str::random(10),
        ];
    }

    public function ciudadano(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => \App\Enums\Rol::CIUDADANO->value,
        ]);
    }

    public function empresa(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => \App\Enums\Rol::EMPRESA->value,
        ]);
    }

    public function administrador(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => \App\Enums\Rol::ADMIN->value,
        ]);
    }

    public function pendiente(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => \App\Enums\EstadoUsuario::PENDIENTE->value,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
