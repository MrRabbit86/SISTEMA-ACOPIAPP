<?php

namespace App\Support;

use App\Enums\EstadoOferta;
use App\Models\Oferta;
use App\Models\Reporte;
use App\Models\Transaccion;
use Illuminate\Support\Carbon;

class ReporteService
{
    public function resumen(?string $desde = null, ?string $hasta = null, ?int $empresaId = null): array
    {
        $desde = $desde ? Carbon::parse($desde)->startOfDay() : null;
        $hasta = $hasta ? Carbon::parse($hasta)->endOfDay() : null;

        $ofertas = Oferta::query()
            ->when($desde, fn ($q) => $q->whereDate('fecha_publicacion', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha_publicacion', '<=', $hasta))
            ->get();

        $transacciones = Transaccion::query()
            ->with(['oferta.categoria', 'oferta.ciudadano.usuario'])
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->when($desde, fn ($q) => $q->whereDate('fecha_transaccion', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha_transaccion', '<=', $hasta))
            ->get();

        $pesoTotal = $transacciones->sum(fn ($t) => (float) $t->peso_real_kg);
        $montoTotal = $transacciones->sum(fn ($t) => (float) $t->monto_total);

        $porCategoria = $transacciones->groupBy(fn ($t) => $t->oferta?->categoria?->nombre ?? 'Sin categoría')
            ->map(fn ($items, $material) => [
                'material' => $material,
                'transacciones' => $items->count(),
                'peso_kg' => round($items->sum(fn ($t) => (float) $t->peso_real_kg), 2),
                'monto_total' => round($items->sum(fn ($t) => (float) $t->monto_total), 2),
                'precio_promedio' => round($items->avg(fn ($t) => (float) $t->precio_acordado_kg), 2),
            ])
            ->sortByDesc('monto_total')
            ->values();

        $porMes = $this->porMes($transacciones);
        $topZonas = $this->topZonas($transacciones);

        return [
            'rango' => [
                'desde' => $desde?->toDateString(),
                'hasta' => $hasta?->toDateString(),
            ],
            'ofertas' => [
                'total' => $ofertas->count(),
                'activas' => $ofertas->whereIn('estado', [
                    EstadoOferta::PENDIENTE->value,
                    EstadoOferta::EN_PROCESO->value,
                ])->count(),
                'completadas' => $ofertas->where('estado', EstadoOferta::COMPLETADA->value)->count(),
                'canceladas' => $ofertas->where('estado', EstadoOferta::CANCELADA->value)->count(),
            ],
            'transacciones' => [
                'total' => $transacciones->count(),
                'peso_total_kg' => round($pesoTotal, 2),
                'monto_total' => round($montoTotal, 2),
                'monto_promedio' => round($transacciones->avg(fn ($t) => (float) $t->monto_total), 2),
            ],
            'por_categoria' => $porCategoria,
            'por_mes' => $porMes,
            'top_zonas' => $topZonas,
        ];
    }

    public function registrar(string $tipo, array $parametros, array $resultado): Reporte
    {
        return Reporte::create([
            'tipo' => $tipo,
            'parametros' => $parametros,
            'resultado' => $resultado,
            'fecha_generacion' => now(),
        ]);
    }

    private function porMes($transacciones): array
    {
        $porMes = $transacciones->groupBy(fn ($t) => $t->fecha_transaccion?->format('Y-m'))
            ->map(fn ($items, $mes) => [
                'mes' => $mes,
                'etiqueta' => Carbon::createFromFormat('Y-m', $mes)->translatedFormat('M Y'),
                'transacciones' => $items->count(),
                'monto_total' => round($items->sum(fn ($t) => (float) $t->monto_total), 2),
            ]);

        $inicio = now()->startOfMonth()->subMonths(11);
        $inicio = $inicio->min($transacciones->min(fn ($t) => $t->fecha_transaccion)?->startOfMonth() ?? $inicio);

        $claves = collect();
        for ($i = 0; $i < 12; $i++) {
            $claves->push($inicio->copy()->addMonths($i)->format('Y-m'));
        }

        return $claves->map(function (string $mes) use ($porMes): array {
            return $porMes->get($mes, [
                'mes' => $mes,
                'etiqueta' => Carbon::createFromFormat('Y-m', $mes)->translatedFormat('M Y'),
                'transacciones' => 0,
                'monto_total' => 0,
            ]);
        })->values()->all();
    }

    private function topZonas($transacciones): array
    {
        return $transacciones->groupBy(fn ($t) => $t->oferta?->ciudadano?->zona ?? 'Zona Sur')
            ->map(fn ($items, $zona) => [
                'zona' => $zona,
                'transacciones' => $items->count(),
                'monto_total' => round($items->sum(fn ($t) => (float) $t->monto_total), 2),
            ])
            ->sortByDesc('monto_total')
            ->values()
            ->all();
    }
}