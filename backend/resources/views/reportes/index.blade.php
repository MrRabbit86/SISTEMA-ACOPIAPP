@extends('layouts.app')

@section('title', 'Reportes')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Reportes y estadísticas</h1>
            <p class="text-sm text-gray-500">
                Comportamiento de las ofertas y transacciones de compra-venta de residuos.
            </p>
        </div>
        <a href="{{ route('reportes.pdf', request()->query()) }}" target="_blank"
           class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-600">
            Exportar PDF
        </a>
    </div>

    <div class="mb-6 rounded-lg bg-white p-4 shadow">
        <form method="GET" action="{{ route('reportes.index') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Desde</label>
                <input type="date" name="desde" value="{{ $desde }}"
                       class="rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Hasta</label>
                <input type="date" name="hasta" value="{{ $hasta }}"
                       class="rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <button type="submit"
                    class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-600">
                Aplicar periodo
            </button>
            <a href="{{ route('reportes.index') }}"
               class="ml-1 rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                Limpiar
            </a>
        </form>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @php
            $tarjetas = [
                ['Ofertas totales', $datos['ofertas']['total'], 'text-gray-900'],
                ['Ofertas activas', $datos['ofertas']['activas'], 'text-emerald-700'],
                ['Ofertas completadas', $datos['ofertas']['completadas'], 'text-sky-700'],
                ['Ofertas canceladas', $datos['ofertas']['canceladas'], 'text-red-600'],
                ['Transacciones', $datos['transacciones']['total'], 'text-gray-900'],
                ['Peso total', number_format($datos['transacciones']['peso_total_kg'], 2).' kg', 'text-gray-900'],
                ['Monto total', 'Bs '.number_format($datos['transacciones']['monto_total'], 2), 'text-emerald-700'],
                ['Monto promedio', 'Bs '.number_format($datos['transacciones']['monto_promedio'], 2), 'text-gray-900'],
            ];
        @endphp
        @foreach ($tarjetas as [$label, $valor, $color])
            <div class="rounded-lg bg-white p-4 shadow ring-1 ring-gray-200">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ $label }}</p>
                <p class="mt-1 text-2xl font-semibold {{ $color }}">{{ $valor }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-lg bg-white p-6 shadow ring-1 ring-gray-200">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">Montos por material</h2>
            @php $maxMonto = max($datos['por_categoria']->pluck('monto_total')->push(0)->all()); @endphp
            <div class="space-y-3">
                @forelse ($datos['por_categoria'] as $categoria)
                    <div>
                        <div class="mb-1 flex justify-between text-sm">
                            <span class="font-medium">{{ $categoria['material'] }}</span>
                            <span class="text-gray-500">
                                Bs {{ number_format($categoria['monto_total'], 2) }}
                                · {{ number_format($categoria['peso_kg'], 2) }} kg
                            </span>
                        </div>
                        <div class="h-3 w-full overflow-hidden rounded bg-gray-100">
                            <div class="h-full rounded bg-emerald-600"
                                 style="width: {{ $maxMonto > 0 ? round($categoria['monto_total'] * 100 / $maxMonto) : 0 }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Sin transacciones en el periodo.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-lg bg-white p-6 shadow ring-1 ring-gray-200">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">Transacciones por mes</h2>
            <div class="space-y-3">
                @php
                $meses = $datos['por_mes'] ?? [];
                $maxMes = $meses ? max(array_map(fn ($m) => $m['monto_total'], $meses)) : 0;
            @endphp
            @foreach (array_reverse($meses) as $mes)
                <div>
                    <div class="mb-1 flex justify-between text-sm">
                        <span class="font-medium">{{ $mes['etiqueta'] }}</span>
                        <span class="text-gray-500">{{ $mes['transacciones'] }} · Bs {{ number_format($mes['monto_total'], 2) }}</span>
                    </div>
                    <div class="h-3 w-full overflow-hidden rounded bg-gray-100">
                        <div class="h-full rounded bg-sky-600"
                             style="width: {{ $maxMes > 0 ? round($mes['monto_total'] * 100 / $maxMes) : 0 }}%"></div>
                    </div>
                </div>
            @endforeach
            </div>
        </div>
    </div>

    @if (!empty($historial->count()))
        <div class="mt-6 overflow-x-auto rounded-lg bg-white shadow ring-1 ring-gray-200">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Historial de reportes generados</h2>
            </div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                        <th class="px-6 py-3">Tipo</th>
                        <th class="px-6 py-3">Periodo</th>
                        <th class="px-6 py-3">Fecha de generación</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($historial as $reporte)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-medium">{{ strtoupper($reporte->tipo) }}</td>
                            <td class="px-6 py-3 text-gray-600">
                                {{ $reporte->parametros['desde'] ?? 'Inicio' }}
                                al {{ $reporte->parametros['hasta'] ?? 'actualidad' }}
                            </td>
                            <td class="px-6 py-3 text-gray-500">{{ $reporte->fecha_generacion?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection