@extends('layouts.app')

@section('title', 'Transacciones')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Transacciones</h1>
            <p class="text-sm text-gray-500">
                Registro de compras con peso real y monto calculado automáticamente.
            </p>
        </div>
        @if (auth()->user()?->esEmpresa())
            <a href="{{ route('ofertas.mapa') }}"
               class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-600">
                Nueva compra
            </a>
        @endif
    </div>

    @php
        $totalMonto = $transacciones->sum('monto_total');
        $totalPeso = $transacciones->sum('peso_real_kg');
    @endphp

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-lg bg-white p-4 shadow ring-1 ring-gray-200">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Transacciones</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $transacciones->count() }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow ring-1 ring-gray-200">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Peso total comprado</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($totalPeso, 2) }} kg</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow ring-1 ring-gray-200">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Monto total pagado</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-700">Bs {{ number_format($totalMonto, 2) }}</p>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg bg-white shadow ring-1 ring-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr class="text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                    <th class="px-4 py-3">Oferta</th>
                    <th class="px-4 py-3">Vendedor</th>
                    <th class="px-4 py-3">Empresa</th>
                    <th class="px-4 py-3">Peso real (kg)</th>
                    <th class="px-4 py-3">Precio (Bs/kg)</th>
                    <th class="px-4 py-3">Monto total</th>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($transacciones as $transaccion)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('ofertas.show', $transaccion->oferta) }}"
                               class="font-medium text-emerald-700 hover:underline">
                                #{{ $transaccion->oferta->id }} — {{ $transaccion->oferta->categoria?->nombre }}
                            </a>
                        </td>
                        <td class="px-4 py-3">{{ $transaccion->oferta->ciudadano?->usuario?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $transaccion->empresa?->empresa?->razon_social ?? $transaccion->empresa?->name }}</td>
                        <td class="px-4 py-3">{{ number_format((float) $transaccion->peso_real_kg, 2) }}</td>
                        <td class="px-4 py-3">{{ number_format((float) $transaccion->precio_acordado_kg, 2) }}</td>
                        <td class="px-4 py-3 font-semibold text-emerald-700">
                            Bs {{ number_format((float) $transaccion->monto_total, 2) }}
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $transaccion->fecha_transaccion?->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('comprobantes.mostrar', $transaccion) }}" target="_blank"
                               class="inline-flex items-center gap-1 text-sm font-medium text-emerald-700 hover:underline">
                                Comprobante
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                            Aún no hay transacciones registradas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection