@extends('layouts.app')

@section('title', 'Detalle de oferta')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@endpush

@section('content')
    <div class="mb-6">
        <a href="{{ route('ofertas.mapa') }}" class="text-sm text-emerald-700 hover:underline">&larr; Volver al mapa</a>
        <div class="mt-2 flex items-center justify-between">
            <h1 class="flex items-center gap-2 text-2xl font-semibold text-gray-900">
                <x-material-icon :categoria="$oferta->categoria"/>
                Oferta #{{ $oferta->id }} — {{ $oferta->categoria?->nombre }}
            </h1>
            <x-estado-badge :estado="$oferta->estado->value" :label="$oferta->estado->label()" />
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="space-y-4">
            <div class="rounded-lg bg-white p-6 shadow ring-1 ring-gray-200">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">Datos de la oferta</h2>
                <dl class="space-y-3 text-sm">
                    @if ($oferta->foto_url)
                        <img src="{{ asset('storage/'.e($oferta->foto_url)) }}" alt="Foto de la oferta"
                             class="h-40 w-full rounded-md object-cover ring-1 ring-gray-200">
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Material</dt>
                        <dd class="font-medium">{{ $oferta->categoria?->nombre }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Cantidad estimada</dt>
                        <dd class="font-medium">{{ $oferta->cantidad_estimada_kg }} kg</dd>
                    </div>
                    @if ($oferta->descripcion)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Descripción</dt>
                            <dd class="max-w-[14rem] text-right font-medium">{{ $oferta->descripcion }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Entrega</dt>
                        <dd class="font-medium">{{ $oferta->modalidadLabel() }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Precio de referencia</dt>
                        <dd class="font-medium">Bs {{ number_format((float) $oferta->categoria?->precio_referencia_kg, 2) }}/kg</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Fecha de publicación</dt>
                        <dd class="font-medium">{{ $oferta->fecha_publicacion?->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Coordenadas</dt>
                        <dd class="font-mono text-xs">{{ $oferta->latitud }}, {{ $oferta->longitud }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-lg bg-white p-6 shadow ring-1 ring-gray-200">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">Vendedor</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Nombre</dt>
                        <dd class="font-medium">{{ $oferta->ciudadano?->usuario?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Zona</dt>
                        <dd class="font-medium">{{ $oferta->ciudadano?->zona ?? 'Zona Sur' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Teléfono</dt>
                        <dd class="font-medium">{{ $oferta->ciudadano?->usuario?->telefono ?? '—' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="rounded-lg bg-white p-4 shadow ring-1 ring-gray-200">
            <div id="mapa" class="h-[400px] w-full rounded-md"></div>
        </div>
    </div>

    @if (auth()->user()?->esEmpresa())
        @if (in_array($oferta->estado->value, ['pendiente', 'en_proceso'], true))
            <div class="mt-6 rounded-lg bg-white p-6 shadow ring-1 ring-gray-200">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">Registrar compra</h2>
                <p class="mb-4 text-sm text-gray-600">
                    Ingrese el peso real medido en balanza. El monto se calcula automáticamente con el precio de
                    referencia (<strong>Bs {{ number_format((float) $oferta->obtenerPrecioReferencia(), 2) }}/kg</strong>)
                    o con un precio acordado.
                </p>
                <form method="POST" action="{{ route('ofertas.transaccion.store', $oferta) }}" class="grid gap-4 max-w-xl"
                      data-compras-form>
                    @csrf
                    <div>
                        <label for="peso_real_kg" class="mb-1 block text-sm font-medium text-gray-700">Peso real (kg)</label>
                        <input type="number" step="0.01" min="0.01" required name="peso_real_kg" id="peso_real_kg"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                               placeholder="Ej: 45.50" data-compras-peso>
                        @error('peso_real_kg') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="precio_acordado_kg" class="mb-1 block text-sm font-medium text-gray-700">
                            Precio acordado Bs/kg <span class="text-xs text-gray-400">(opcional)</span>
                        </label>
                        <input type="number" step="0.01" min="0.01" name="precio_acordado_kg" id="precio_acordado_kg"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                               placeholder="Bs {{ number_format((float) $oferta->obtenerPrecioReferencia(), 2) }}"
                               data-compras-precio>
                        @error('precio_acordado_kg') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-center gap-4">
                        <button type="submit"
                                class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                            Confirmar compra
                        </button>
                        <p class="text-sm text-gray-700">
                            Monto total: <strong class="text-emerald-700" data-compras-monto>Bs 0,00</strong>
                        </p>
                    </div>
                </form>
            </div>
        @elseif ($oferta->transaccion)
            <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 p-6">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-emerald-800">Compra registrada</h2>
                <dl class="grid gap-3 text-sm sm:grid-cols-3">
                    <div>
                        <dt class="text-emerald-700">Peso real</dt>
                        <dd class="font-semibold text-emerald-900">{{ number_format((float) $oferta->transaccion->peso_real_kg, 2) }} kg</dd>
                    </div>
                    <div>
                        <dt class="text-emerald-700">Precio</dt>
                        <dd class="font-semibold text-emerald-900">Bs {{ number_format((float) $oferta->transaccion->precio_acordado_kg, 2) }}/kg</dd>
                    </div>
                    <div>
                        <dt class="text-emerald-700">Monto total</dt>
                        <dd class="font-semibold text-emerald-900">Bs {{ number_format((float) $oferta->transaccion->monto_total, 2) }}</dd>
                    </div>
                </dl>
                <a href="{{ route('transacciones.index') }}" class="mt-3 inline-block text-sm text-emerald-700 hover:underline">
                    Ver todas las transacciones &rarr;
                </a>
                <a href="{{ route('comprobantes.mostrar', $oferta->transaccion) }}" target="_blank"
                   class="mt-3 ml-3 inline-block rounded-md bg-emerald-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-emerald-700">
                    Descargar comprobante
                </a>
            </div>
        @endif
    @endif
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    const oferta = @json($geo);

    const mapa = L.map('mapa').setView([oferta.lat, oferta.lng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(mapa);

    L.circleMarker([oferta.lat, oferta.lng], {
        radius: 9,
        color: '#10b981',
        fillColor: '#10b981',
        fillOpacity: 0.6,
    }).addTo(mapa).bindPopup(`<strong>${oferta.material}</strong>`).openPopup();

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('[data-compras-form]');
        if (!form) return;
        const peso = form.querySelector('[data-compras-peso]');
        const precio = form.querySelector('[data-compras-precio]');
        const referencia = {{ number_format((float) $oferta->obtenerPrecioReferencia(), 2, '.', '') }};
        const monto = form.querySelector('[data-compras-monto]');

        const precioValido = () => {
            const v = parseFloat(precio?.value);
            return !isNaN(v) && v > 0;
        };

        const actualizar = () => {
            const p = parseFloat(peso?.value) || 0;
            const c = precioValido() ? parseFloat(precio.value) : referencia;
            monto.textContent = 'Bs ' + (p * c).toFixed(2).replace('.', ',');
        };

        peso?.addEventListener('input', actualizar);
        precio?.addEventListener('input', actualizar);
    });
</script>
@endpush