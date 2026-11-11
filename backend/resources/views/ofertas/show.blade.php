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
                @php
                    $telCiudadano = $oferta->ciudadano?->usuario?->telefono;
                    $puedeContactarCiudadano = in_array($oferta->estado->value, ['pendiente', 'en_proceso'], true)
                        && (! $oferta->empresa_reservada_id || $oferta->empresa_reservada_id === auth()->id());
                    $waCiudadano = $puedeContactarCiudadano
                        ? \App\Support\ContactoTelefono::urlWhatsapp(
                            $telCiudadano,
                            "Hola {$oferta->ciudadano?->usuario?->name}, soy de ".auth()->user()?->name." (ACOPIAPP). Te escribo por tu oferta #{$oferta->id} de {$oferta->categoria?->nombre}.",
                        )
                        : null;
                    $telLinkCiudadano = $puedeContactarCiudadano
                        ? \App\Support\ContactoTelefono::urlLlamada($telCiudadano)
                        : null;
                @endphp
                @if ($waCiudadano || $telLinkCiudadano)
                    <div class="mt-4 flex flex-wrap gap-2 border-t border-gray-100 pt-3">
                        @if ($waCiudadano)
                            <a href="{{ $waCiudadano }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700">
                                WhatsApp al ciudadano
                            </a>
                        @elseif ($telLinkCiudadano)
                            <a href="{{ $telLinkCiudadano }}"
                               class="inline-flex items-center rounded-md bg-gray-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-800">
                                Llamar al ciudadano
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="rounded-lg bg-white p-4 shadow ring-1 ring-gray-200">
            <div id="mapa" class="h-[400px] w-full rounded-md"></div>
            <div class="mt-3 flex items-center justify-between text-xs text-gray-500">
                <span>Ubicación del material</span>
                <a href="{{ $oferta->urlGoogleMaps() }}" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center rounded-md border border-emerald-200 bg-emerald-50 px-3 py-1.5 font-medium text-emerald-800 hover:bg-emerald-100">
                    Abrir en Google Maps &rarr;
                </a>
            </div>
        </div>
    </div>

    @if (auth()->user()?->esEmpresa())
        @if ($errors->has('estado'))
            <div class="mt-6 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">
                {{ $errors->first('estado') }}
            </div>
        @endif

        @php
            $reservadaPorOtro = $oferta->estado->value === 'en_proceso'
                && $oferta->empresa_reservada_id !== null
                && $oferta->empresa_reservada_id !== auth()->id();
            $reservadaPorMi = $oferta->estado->value === 'en_proceso'
                && $oferta->empresa_reservada_id === auth()->id();
        @endphp

        @if ($reservadaPorOtro)
            <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-6">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-amber-800">Oferta reservada por otra empresa</h2>
                <p class="mt-2 text-sm text-amber-900">
                    Otra empresa acopiadora reservó este recojo y se encuentra en camino
                    @if ($oferta->reservada_hasta)
                        (reserva vigente hasta las <strong>{{ $oferta->reservada_hasta->format('H:i') }}</strong>).
                    @endif
                    Si no concreta la compra, la oferta volverá a quedar disponible automáticamente.
                </p>
            </div>
        @elseif (in_array($oferta->estado->value, ['pendiente', 'en_proceso'], true))
            @if ($oferta->estado->value === 'pendiente')
                <div class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-lg border border-emerald-200 bg-emerald-50 p-5">
                    <div>
                        <h2 class="text-sm font-semibold text-emerald-900">¿Vas en camino a recoger este material?</h2>
                        <p class="mt-1 text-xs text-emerald-800">
                            Reserva la oferta por 2 horas para que otras empresas no dupliquen el viaje mientras te trasladas.
                        </p>
                    </div>
                    <form method="POST" action="{{ route('ofertas.reservar', $oferta) }}">
                        @csrf
                        <button type="submit"
                                class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800">
                            Reservar recojo (Voy en camino)
                        </button>
                    </form>
                </div>
            @elseif ($reservadaPorMi)
                <div class="mt-6 rounded-lg border border-blue-200 bg-blue-50 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold text-blue-900">Tienes reservado este recojo</h2>
                            <p class="mt-1 text-xs text-blue-800">
                                Vigente hasta {{ $oferta->reservada_hasta?->format('d/m/Y H:i') }}.
                                Pide al ciudadano el código de entrega de 4 dígitos al pesar el material para registrar la compra.
                            </p>
                            @if ($oferta->llegada_en)
                                <p class="mt-1.5 inline-block rounded bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-900">
                                    Llegada notificada al ciudadano a las {{ $oferta->llegada_en->format('H:i') }}
                                </p>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <form method="POST" action="{{ route('ofertas.llegada', $oferta) }}">
                                @csrf
                                <button type="submit"
                                        class="rounded-md bg-blue-700 px-3.5 py-2 text-xs font-medium text-white hover:bg-blue-800">
                                    {{ $oferta->llegada_en ? 'Volver a avisar llegada' : 'Ya estoy en el punto' }}
                                </button>
                            </form>
                            @if ($waCiudadano)
                                <a href="{{ $waCiudadano }}" target="_blank" rel="noopener noreferrer"
                                   class="rounded-md bg-emerald-600 px-3.5 py-2 text-xs font-medium text-white hover:bg-emerald-700">
                                    WhatsApp al ciudadano
                                </a>
                            @elseif ($telLinkCiudadano)
                                <a href="{{ $telLinkCiudadano }}"
                                   class="rounded-md bg-gray-700 px-3.5 py-2 text-xs font-medium text-white hover:bg-gray-800">
                                    Llamar
                                </a>
                            @endif
                        </div>
                    </div>
                    <form method="POST" action="{{ route('ofertas.liberar', $oferta) }}"
                          class="mt-4 flex flex-wrap items-end gap-3 border-t border-blue-200 pt-4">
                        @csrf
                        <div>
                            <label for="motivo" class="mb-1 block text-xs font-medium text-blue-900">Si no se concretó el recojo</label>
                            <select name="motivo" id="motivo" required
                                    class="rounded-md border-blue-300 bg-white text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @foreach ($motivosLiberacion as $valor => $etiqueta)
                                    <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="min-w-[200px] flex-1">
                            <label for="nota" class="mb-1 block text-xs font-medium text-blue-900">Observación (opcional)</label>
                            <input type="text" name="nota" id="nota" maxlength="200"
                                   placeholder="Ej: No hubo acuerdo en el precio / domicilio cerrado"
                                   class="w-full rounded-md border-blue-300 bg-white text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <button type="submit"
                                class="rounded-md border border-blue-300 bg-white px-3 py-2 text-xs font-medium text-blue-900 hover:bg-blue-100">
                            Liberar / Reportar
                        </button>
                    </form>
                </div>
            @endif

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
                        <input type="text" inputmode="decimal" required name="peso_real_kg" id="peso_real_kg"
                               value="{{ old('peso_real_kg') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                               placeholder="Ej: 45.50" data-compras-peso>
                        @error('peso_real_kg') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="precio_acordado_kg" class="mb-1 block text-sm font-medium text-gray-700">
                            Precio acordado Bs/kg <span class="text-xs text-gray-400">(opcional)</span>
                        </label>
                        <input type="text" inputmode="decimal" name="precio_acordado_kg" id="precio_acordado_kg"
                               value="{{ old('precio_acordado_kg') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                               placeholder="Bs {{ number_format((float) $oferta->obtenerPrecioReferencia(), 2) }}"
                               data-compras-precio>
                        @error('precio_acordado_kg') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="rounded-md bg-gray-50 p-3 ring-1 ring-gray-200">
                        <label for="codigo_confirmacion" class="mb-1 block text-sm font-medium text-gray-700">
                            Código de entrega del ciudadano (4 dígitos)
                        </label>
                        <input type="text" inputmode="numeric" maxlength="4" pattern="\d{4}"
                               name="codigo_confirmacion" id="codigo_confirmacion"
                               value="{{ old('codigo_confirmacion') }}" required
                               class="w-40 rounded-md border-gray-300 font-mono text-base tracking-widest shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                               placeholder="Ej: 4821">
                        @error('codigo_confirmacion') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        <label class="mt-2 flex items-center gap-2 text-xs text-gray-600">
                            <input type="checkbox" name="sin_codigo_presencial" id="sin_codigo_presencial" value="1"
                                   class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                   {{ old('sin_codigo_presencial') ? 'checked' : '' }}>
                            <span>El ciudadano no tiene su código a mano (registro directo en sitio)</span>
                        </label>
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

        const parseNum = (val) => parseFloat(String(val ?? '').replace(',', '.'));

        const precioValido = () => {
            const v = parseNum(precio?.value);
            return !isNaN(v) && v > 0;
        };

        const actualizar = () => {
            const p = parseNum(peso?.value) || 0;
            const c = precioValido() ? parseNum(precio.value) : referencia;
            monto.textContent = 'Bs ' + (p * c).toFixed(2).replace('.', ',');
        };

        const pinInput = form.querySelector('#codigo_confirmacion');
        const sinPinCheckbox = form.querySelector('#sin_codigo_presencial');
        const sincronizarPin = () => {
            if (!pinInput || !sinPinCheckbox) return;
            pinInput.required = !sinPinCheckbox.checked;
            if (sinPinCheckbox.checked) {
                pinInput.value = '';
            }
        };
        sinPinCheckbox?.addEventListener('change', sincronizarPin);
        sincronizarPin();

        peso?.addEventListener('input', actualizar);
        precio?.addEventListener('input', actualizar);
    });
</script>
@endpush