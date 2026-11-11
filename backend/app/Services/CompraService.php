<?php

namespace App\Services;

use App\Enums\EstadoOferta;
use App\Enums\EstadoTransaccion;
use App\Models\Oferta;
use App\Models\Transaccion;
use App\Models\User;
use App\Notifications\NotificacionSistema;
use App\Support\Result;
use Illuminate\Support\Facades\DB;

class CompraService
{
    public function registrar(User $empresa, Oferta $oferta, array $datos): Result
    {
        Oferta::liberarReservasExpiradas();

        $resultado = DB::transaction(function () use ($empresa, $oferta, $datos): Result {
            // Bloqueo de fila: serializa compras concurrentes sobre la misma
            // oferta y evita violar el UNIQUE de transacciones.oferta_id.
            $oferta = Oferta::query()->lockForUpdate()->find($oferta->id);

            if (! $oferta || ! in_array($oferta->estado, [EstadoOferta::PENDIENTE, EstadoOferta::EN_PROCESO], true)) {
                return Result::fail(
                    'La oferta ya fue adquirida o no está disponible para registrar una compra.',
                    'oferta_no_disponible',
                );
            }

            if (
                $oferta->estado === EstadoOferta::EN_PROCESO
                && $oferta->empresa_reservada_id !== null
                && $oferta->empresa_reservada_id !== $empresa->id
                && ($oferta->reservada_hasta === null || $oferta->reservada_hasta->isFuture())
            ) {
                return Result::fail(
                    'La oferta está reservada en proceso de recojo por otra empresa.',
                    'oferta_reservada',
                );
            }

            if (
                ! empty($datos['codigo_confirmacion'])
                && $oferta->codigo_confirmacion !== null
                && (string) $datos['codigo_confirmacion'] !== (string) $oferta->codigo_confirmacion
            ) {
                return Result::fail(
                    'El código de confirmación del ciudadano no coincide.',
                    'codigo_invalido',
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

            $oferta->update([
                'estado' => EstadoOferta::COMPLETADA,
                'reservada_hasta' => null,
            ]);

            return Result::ok($transaccion->fresh());
        });

        if ($resultado->bien()) {
            $this->notificarCompra($empresa, $resultado->valor());
        }

        return $resultado;
    }

    private function notificarCompra(User $empresa, Transaccion $transaccion): void
    {
        $transaccion->loadMissing(['oferta.categoria', 'oferta.ciudadano.usuario']);
        $oferta = $transaccion->oferta;
        $material = $oferta?->categoria?->nombre ?? 'material';
        $peso = number_format((float) $transaccion->peso_real_kg, 2);
        $monto = number_format((float) $transaccion->monto_total, 2);
        $puntos = (int) ($transaccion->puntos_otorgados ?? 0);

        $dueno = $oferta?->ciudadano?->usuario;
        if ($dueno) {
            $dueno->notify(new NotificacionSistema(
                titulo: '¡Compra confirmada!',
                mensaje: "{$empresa->name} compró {$peso} kg de {$material} por Bs {$monto}. Ganaste +{$puntos} pts.",
                url: route('mis-ofertas'),
                tipo: 'compra_confirmada',
            ));
        }

        $empresa->notify(new NotificacionSistema(
            titulo: 'Compra registrada correctamente',
            mensaje: "Adquiriste {$peso} kg de {$material} por Bs {$monto}. Ya puedes descargar el comprobante.",
            url: route('transacciones.index'),
            tipo: 'compra_registrada',
        ));
    }
}
