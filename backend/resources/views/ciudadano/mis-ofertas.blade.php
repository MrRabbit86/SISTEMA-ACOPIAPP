@extends('layouts.app')

@section('title', 'Mis ofertas')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Mis ofertas</h1>
            <p class="text-sm text-gray-500">Publica tu material y las empresas lo ven en su mapa.</p>
        </div>
        <span class="rounded-full bg-amber-100 px-4 py-1.5 text-sm font-semibold text-amber-800">
            ⭐ {{ number_format($ciudadano?->puntos ?? 0) }} puntos verdes
        </span>
    </div>

    <div class="grid gap-6 md:grid-cols-3">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200 md:col-span-1">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">Publicar nueva oferta</h2>

            <form method="POST" action="{{ route('mis-ofertas.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <span class="mb-1 block text-sm font-medium">Material (categoría)</span>
                    <input type="hidden" name="categoria_id" id="categoria_id" required
                           value="{{ old('categoria_id', $categorias->first()?->id) }}">
                    <div class="grid grid-cols-2 gap-2" role="radiogroup" aria-label="Material">
                        @foreach ($categorias as $categoria)
                            <button type="button" data-categoria-btn value="{{ $categoria->id }}"
                                    data-precio="{{ $categoria->precio_referencia_kg }}"
                                    class="flex flex-col items-center gap-1.5 rounded-xl border-2 px-2 py-3 text-xs font-medium transition active:scale-[0.97]">
                                <x-material-icon :categoria="$categoria" class="h-5 w-5"/>
                                <span class="leading-tight">{{ $categoria->nombre }}</span>
                            </button>
                        @endforeach
                    </div>
                    <p id="precio-hint" class="mt-2 text-xs text-gray-500"></p>
                </div>

                <div>
                    <label for="cantidad_estimada_kg" class="mb-1 block text-sm font-medium">Peso aproximado (kg)</label>
                    <input type="number" name="cantidad_estimada_kg" id="cantidad_estimada_kg"
                           value="{{ old('cantidad_estimada_kg') }}" step="0.01" min="0.01" required
                           class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>

                <div>
                    <label for="descripcion" class="mb-1 block text-sm font-medium">Descripción breve (opcional)</label>
                    <textarea name="descripcion" id="descripcion" rows="2" maxlength="255"
                              placeholder="Ej.: botellas limpias, papel de oficina…"
                              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">{{ old('descripcion') }}</textarea>
                </div>

                <div>
                    <label for="modalidad_entrega" class="mb-1 block text-sm font-medium">Modalidad de entrega</label>
                    <select name="modalidad_entrega" id="modalidad_entrega"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        <option value="entrega_punto_verde" @selected(old('modalidad_entrega', 'entrega_punto_verde') === 'entrega_punto_verde')>Entrego en el punto verde</option>
                        <option value="recojo_domicilio" @selected(old('modalidad_entrega') === 'recojo_domicilio')>Recojo a domicilio</option>
                    </select>
                </div>

                <div>
                    <label for="foto" class="mb-1 block text-sm font-medium">Foto del material (opcional)</label>
                    <input type="file" name="foto" id="foto" accept="image/jpeg,image/png,image/webp"
                           class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-emerald-700 focus:border-emerald-500 focus:outline-none">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Ubicación (Zona Sur)</label>
                    <button type="button" id="btn-gps"
                            class="group flex w-full items-center justify-center gap-2 rounded-lg border-2 border-dashed border-emerald-400 bg-white px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:border-emerald-500 hover:bg-emerald-50 active:scale-[0.98]">
                        <svg id="gps-icono" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 1 1 16 0Z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <svg id="gps-spinner" class="hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5" opacity="0.25"/>
                            <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                        </svg>
                        <span id="gps-texto">Usar mi ubicación</span>
                    </button>
                    <p id="gps-estado" class="mt-1 text-xs text-gray-500">Completa la latitud y longitud automáticamente.</p>
                    <div class="mt-3 grid grid-cols-2 gap-3">
                        <div>
                            <input type="text" name="latitud" id="latitud" inputmode="decimal"
                                   value="{{ old('latitud') }}" placeholder="Latitud (-17.5 a -17.2)" required
                                   class="w-full rounded-md border border-gray-300 px-3 py-2 font-mono text-xs focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        </div>
                        <div>
                            <input type="text" name="longitud" id="longitud" inputmode="decimal"
                                   value="{{ old('longitud') }}" placeholder="Longitud (-66.3 a -66.0)" required
                                   class="w-full rounded-md border border-gray-300 px-3 py-2 font-mono text-xs focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        </div>
                    </div>
                </div>

                <button type="submit"
                        class="w-full rounded-md bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-600">
                    Publicar oferta
                </button>
            </form>
        </div>

        <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-200 md:col-span-2">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                        <th class="px-4 py-3">Foto</th>
                        <th class="px-4 py-3">Material</th>
                        <th class="px-4 py-3">Cantidad</th>
                        <th class="px-4 py-3">Entrega</th>
                        <th class="px-4 py-3">Venta</th>
                        <th class="px-4 py-3">Publicado</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($ofertas as $oferta)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                @if ($oferta->foto_url)
                                    <img src="{{ asset('storage/'.e($oferta->foto_url)) }}" alt="Foto de {{ $oferta->categoria?->nombre }}"
                                         class="h-12 w-12 rounded-md object-cover ring-1 ring-gray-200">
                                @else
                                    <x-material-icon :categoria="$oferta->categoria" class="h-4 w-4"/>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium">{{ $oferta->categoria?->nombre }}</p>
                                @if ($oferta->descripcion)
                                    <p class="max-w-[16rem] text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($oferta->descripcion, 60) }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $oferta->cantidad_estimada_kg }} kg</td>
                            <td class="px-4 py-3 text-xs">{{ $oferta->modalidadLabel() }}</td>
                            <td class="px-4 py-3">
                                @if ($oferta->transaccion)
                                    <p class="text-xs">
                                        <span class="font-medium">{{ $oferta->transaccion->empresa?->name }}</span><br>
                                        {{ $oferta->transaccion->peso_real_kg }} kg · Bs {{ $oferta->transaccion->monto_total }}<br>
                                        <span class="text-gray-400">{{ $oferta->transaccion->fecha_transaccion?->format('d/m/Y H:i') }}</span>
                                    </p>
                                    <a href="{{ route('comprobantes.mostrar', $oferta->transaccion) }}"
                                       class="mt-1 inline-block text-xs font-medium text-emerald-700 hover:underline">
                                        Descargar comprobante
                                    </a>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $oferta->fecha_publicacion?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <x-estado-badge :estado="$oferta->estado->value" :label="$oferta->estado->label()" />
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if (in_array($oferta->estado->value, ['pendiente', 'en_proceso']))
                                    <form method="POST" action="{{ route('mis-ofertas.cancelar', $oferta) }}"
                                          onsubmit="return confirm('¿Cancelar esta oferta?');" class="inline">
                                        @csrf
                                        <button class="text-red-600 hover:underline">Cancelar</button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-gray-500">
                            <p class="text-3xl">🌱</p>
                            <p class="mt-2">Aún no has publicado ofertas. Publica la primera desde el formulario.</p>
                        </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const precioHint = document.getElementById('precio-hint');
        const inputCategoria = document.getElementById('categoria_id');
        const botonesCategoria = Array.prototype.slice.call(document.querySelectorAll('[data-categoria-btn]'));

        function pintarSeleccion() {
            botonesCategoria.forEach(function (btn) {
                const activo = btn.value === String(inputCategoria.value);
                btn.classList.toggle('border-emerald-500', activo);
                btn.classList.toggle('bg-emerald-50', activo);
                btn.classList.toggle('text-emerald-800', activo);
                btn.classList.toggle('shadow-sm', activo);
                btn.classList.toggle('border-gray-200', !activo);
                btn.classList.toggle('bg-white', !activo);
                btn.classList.toggle('text-gray-600', !activo);
            });
        }

        function actualizarPrecio() {
            const btn = botonesCategoria.find(function (b) { return b.value === String(inputCategoria.value); });
            precioHint.textContent = btn ? 'Precio de referencia: Bs ' + btn.dataset.precio + ' /kg' : '';
        }

        botonesCategoria.forEach(function (btn) {
            btn.addEventListener('click', function () {
                inputCategoria.value = btn.value;
                pintarSeleccion();
                actualizarPrecio();
            });
        });

        pintarSeleccion();
        actualizarPrecio();

        const btnGps = document.getElementById('btn-gps');
        const gpsIcono = document.getElementById('gps-icono');
        const gpsSpinner = document.getElementById('gps-spinner');
        const gpsTexto = document.getElementById('gps-texto');
        const gpsEstado = document.getElementById('gps-estado');
        const latInput = document.getElementById('latitud');
        const lngInput = document.getElementById('longitud');
        const ZONA = { latMin: -17.5, latMax: -17.2, lngMin: -66.3, lngMax: -66.0 };

        function estadoGps(tipo, mensaje) {
            gpsEstado.textContent = mensaje;
            gpsEstado.className = 'mt-1 text-xs ' + (tipo === 'error'
                ? 'font-medium text-red-600' : tipo === 'exito'
                ? 'font-medium text-emerald-700' : 'text-gray-500');
        }

        function setBuscando(activo) {
            btnGps.disabled = activo;
            gpsIcono.classList.toggle('hidden', activo);
            gpsSpinner.classList.toggle('hidden', !activo);
            if (activo) {
                btnGps.classList.remove('border-dashed', 'border-emerald-400', 'bg-white',
                    'bg-emerald-600', 'text-white');
                btnGps.classList.add('border-solid', 'border-emerald-500', 'bg-emerald-50', 'text-emerald-700');
                gpsTexto.textContent = 'Buscando señal GPS…';
            } else {
                btnGps.classList.remove('border-solid', 'border-emerald-500', 'border-emerald-600',
                    'bg-emerald-50', 'bg-emerald-600', 'text-white');
                btnGps.classList.add('border-dashed', 'border-emerald-400', 'bg-white', 'text-emerald-700');
            }
        }

        btnGps.addEventListener('click', function () {
            if (!navigator.geolocation) {
                estadoGps('error', 'Tu navegador no soporta geolocalización.');
                return;
            }
            const intentar = function () { obtenerUbicacion(); };
            if (navigator.permissions && navigator.permissions.query) {
                navigator.permissions.query({ name: 'geolocation' }).then(function (res) {
                    if (res.state === 'denied') {
                        estadoGps('error', 'Permiso de ubicación denegado. Habilítalo en los ajustes del navegador y vuelve a intentar.');
                        return;
                    }
                    intentar();
                }).catch(intentar);
            } else {
                intentar();
            }
        });

        function manejarPosicion(posicion) {
            const lat = posicion.coords.latitude;
            const lng = posicion.coords.longitude;
            if (lat < ZONA.latMin || lat > ZONA.latMax || lng < ZONA.lngMin || lng > ZONA.lngMax) {
                setBuscando(false);
                estadoGps('error', 'La ubicación está fuera de la Zona Sur de Cochabamba; ingresa las coordenadas manualmente.');
                return;
            }
            latInput.value = lat.toFixed(6);
            lngInput.value = lng.toFixed(6);
            setBuscando(false);
            btnGps.classList.remove('border-dashed', 'border-emerald-400', 'bg-white', 'text-emerald-700');
            btnGps.classList.add('border-solid', 'border-emerald-600', 'bg-emerald-600', 'text-white');
            gpsTexto.textContent = '✓ Ubicación capturada';
            estadoGps('exito', 'Coordenadas listas para publicar.');
        }

        function manejarErrorGps(error) {
            setBuscando(false);
            if (error.code === error.PERMISSION_DENIED) {
                estadoGps('error', 'Permiso de ubicación denegado. Habilítalo en los ajustes del navegador y vuelve a intentar.');
            } else if (error.code === error.TIMEOUT) {
                estadoGps('error', 'No se encontró señal. Activa el GPS y verifica tener datos o WiFi; vuelve a intentar.');
            } else {
                estadoGps('error', 'No se pudo obtener la ubicación; ingresa las coordenadas manualmente.');
            }
        }

        function obtenerUbicacion(fina) {
            fina = fina !== undefined ? fina : true;
            setBuscando(true);
            estadoGps('', fina ? 'Obteniendo ubicación…' : 'Sin señal GPS fina; usando ubicación por red…');
            navigator.geolocation.getCurrentPosition(
                manejarPosicion,
                function (error) {
                    if (fina && (error.code === error.TIMEOUT || error.code === error.POSITION_UNAVAILABLE)) {
                        obtenerUbicacion(false);
                        return;
                    }
                    manejarErrorGps(error);
                },
                fina
                    ? { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                    : { enableHighAccuracy: false, timeout: 10000, maximumAge: 60000 }
            );
        }
    </script>
@endpush