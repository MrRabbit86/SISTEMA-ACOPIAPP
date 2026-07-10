<?php

namespace App\Services;

use App\Enums\EstadoOferta;
use App\Enums\EstadoTransaccion;
use App\Models\Oferta;
use App\Models\Transaccion;
use App\Models\User;
use App\Support\Result;
use Illuminate\Support\Facades\DB;

class CompraService
{
    public function registrar(User $empresa, Oferta $oferta, array $datos): Result
    {
        return DB::transaction(function () use ($empresa, $oferta, $datos): Result {
            // Bloqueo de fila: serializa compras concurrentes sobre la misma
            // oferta y evita violar el UNIQUE de transacciones.oferta_id.
            $oferta = Oferta::query()->lockForUpdate()->find($oferta->id);

            if (! $oferta || ! in_array($oferta->estado, [EstadoOferta::PENDIENTE, EstadoOferta::EN_PROCESO], true)) {
                return Result::fail(
                    'La oferta ya fue adquirida o no está disponible para registrar una compra.',
                    'oferta_no_disponible',
                );
            }

            $precio = isset($datos['precio_acordado_kg'])
                ? round((float) $datos['precio_acordado_kg'], 2)
                : round($oferta->obtenerPrecioReferencia(), 2);

            $monto = round((float) $datos['peso_real_kg'] * $precio, 2);

            $transaccion = Transaccion::create([
                'oferta_id' => $oferta->id,
                'empresa_id' => $empresa->id,
                'peso_real_kg' => $datos['peso_real_kg'],
                'precio_acordado_kg' => $precio,
                'monto_total' => $monto,
                'estado' => EstadoTransaccion::COMPLETADA,
            ]);

            $oferta->update(['estado' => EstadoOferta::COMPLETADA]);

            return Result::ok($transaccion);
        });
    }
}