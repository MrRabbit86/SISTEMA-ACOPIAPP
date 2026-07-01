<?php

namespace Database\Factories;

use App\Enums\EstadoTransaccion;
use App\Models\EmpresaAcopiadora;
use App\Models\Oferta;
use App\Models\Transaccion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaccion>
 */
class TransaccionFactory extends Factory
{
    public function definition(): array
    {
        $empresa = EmpresaAcopiadora::factory()->create();
        $oferta = Oferta::factory()->create();

        return [
            'oferta_id' => $oferta->id,
            'empresa_id' => $empresa->usuario->id,
            'peso_real_kg' => fake()->randomFloat(2, 1, $oferta->cantidad_estimada_kg),
            'precio_acordado_kg' => $oferta->obtenerPrecioReferencia(),
            'monto_total' => 0,
            'estado' => EstadoTransaccion::COMPLETADA->value,
            'fecha_transaccion' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Transaccion $transaccion): void {
            $transaccion->monto_total = round(
                (float) $transaccion->peso_real_kg * (float) $transaccion->precio_acordado_kg,
                2,
            );
        });
    }
}