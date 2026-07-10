@extends('layouts.app')

@section('title', 'Mapa de ofertas')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@endpush

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Mapa de ofertas</h1>
            <p class="text-sm text-gray-500">Ofertas disponibles geolocalizadas en la Zona Sur de Cochabamba.</p>
        </div>
    </div>

    <div class="mb-6 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
        <form method="GET" action="{{ route('ofertas.mapa') }}" class="grid grid-cols-2 gap-4 md:grid-cols-5">
            <div class="col-span-2 md:col-span-5">
                <span class="mb-1 block text-xs font-medium text-gray-600">Material</span>
                <input type="hidden" name="categoria_id" id="filtro-categoria" value="{{ $filtros['categoria_id'] }}">
                <div class="flex flex-wrap gap-2">
                    <button type="button" data-filtro-cat value=""
                            class="rounded-full border px-3 py-1.5 text-xs font-medium transition">
                        Todas
                    </button>
                    @foreach ($categorias as $categoria)
                        <button type="button" data-filtro-cat value="{{ $categoria->id }}"
                                class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-medium transition">
                            <x-material-icon :categoria="$categoria" class="h-3.5 w-3.5"/>
                            {{ $categoria->nombre }}
                        </button>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Estado</label>
                <select name="estado" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <option value="">Disponibles</option>
                    <option value="pendiente" @selected($filtros['estado'] === 'pendiente')>Disponibles</option>
                    <option value="en_proceso" @selected($filtros['estado'] === 'en_proceso')>En proceso</option>
                    <option value="completada" @selected($filtros['estado'] === 'completada')>Completada</option>
                    <option value="cancelada" @selected($filtros['estado'] === 'cancelada')>Cancelada</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Radio (km)</label>
                <input type="number" name="radio" min="1" max="50" value="{{ $filtros['radio'] }}"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Latitud</label>
                <input type="text" name="lat" value="{{ $filtros['lat'] }}"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Longitud</label>
                <input type="text" name="lng" value="{{ $filtros['lng'] }}"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div class="col-span-2 md:col-span-5 md:justify-self-end">
                <button type="submit"
                        class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-600">
                    Filtrar
                </button>
                <a href="{{ route('ofertas.mapa') }}"
                   class="ml-2 rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <div id="mapa" class="h-[500px] w-full rounded-xl shadow-sm ring-1 ring-gray-200"></div>
        </div>
        <div class="max-h-[500px] space-y-3 overflow-y-auto pr-1">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                {{ $ofertas->count() }} oferta(s) en el área
            </h2>
            @forelse ($ofertas as $oferta)
                <a href="{{ route('ofertas.show', $oferta) }}"
                   class="block rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-emerald-300 hover:shadow-md">
                    <div class="flex items-center justify-between gap-2">
                        <span class="inline-flex items-center gap-2 font-medium">
                            <x-material-icon :categoria="$oferta->categoria" class="h-3.5 w-3.5"/>
                            {{ $oferta->categoria?->nombre }}
                        </span>
                        <x-estado-badge :estado="$oferta->estado->value" :label="$oferta->estado->label()" />
                    </div>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ $oferta->cantidad_estimada_kg }} kg estimados ·
                        {{ $oferta->modalidadLabel() }}
                    </p>
                    @if ($oferta->descripcion)
                        <p class="mt-1 text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($oferta->descripcion, 70) }}</p>
                    @endif
                    <p class="mt-1 text-xs text-gray-400">
                        {{ $oferta->ciudadano?->zona ?? 'Zona Sur' }} ·
                        {{ $oferta->fecha_publicacion?->format('d/m/Y H:i') }}
                    </p>
                </a>
            @empty
                <div class="rounded-xl bg-white p-6 text-center text-sm text-gray-500 shadow">
                    <p class="text-3xl">🗺️</p>
                    <p class="mt-2">No hay ofertas que coincidan con los filtros.</p>
                    <a href="{{ route('ofertas.mapa') }}" class="mt-1 inline-block font-medium text-emerald-700 hover:underline">Limpiar filtros</a>
                </div>
            @endforelse
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    const ofertas = @json($marcadores);

    const centro = { lat: {{ $filtros['lat'] }}, lng: {{ $filtros['lng'] }} };

    const mapa = L.map('mapa').setView([centro.lat, centro.lng], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(mapa);

    const colores = {
        pendiente: '#14b8a6',
        en_proceso: '#3b82f6',
        completada: '#10b981',
        cancelada: '#ef4444',
    };

    ofertas.forEach((oferta) => {
        const marcador = L.circleMarker([oferta.lat, oferta.lng], {
            radius: 9,
            color: colores[oferta.estado] ?? '#6b7280',
            fillColor: colores[oferta.estado] ?? '#6b7280',
            fillOpacity: 0.6,
        });

        const foto = oferta.foto
            ? `<img src="${oferta.foto}" style="width:100%;max-height:120px;object-fit:cover;border-radius:6px;margin:6px 0;">`
            : '';
        const descripcion = oferta.descripcion ? `<br><span style="color:#555;">${oferta.descripcion}</span>` : '';
        const modalidad = oferta.modalidad ? `<br>Entrega: ${oferta.modalidad}` : '';

        marcador.bindPopup(`
            ${foto}
            <strong>${oferta.material}</strong><br>
            ${oferta.cantidad} kg estimados<br>
            Zona: ${oferta.zona ?? 'Zona Sur'}<br>
            Estado: ${oferta.estado_label}${modalidad}${descripcion}<br>
            <a href="${oferta.url}" class="text-emerald-600 underline">Ver detalle</a>
        `);

        marcador.addTo(mapa);
    });

    L.circle([centro.lat, centro.lng], {
        radius: {{ $filtros['radio'] }} * 1000,
        color: '#10b981',
        fillColor: '#10b981',
        fillOpacity: 0.08,
    }).addTo(mapa);

    const filtroCategoria = document.getElementById('filtro-categoria');
    const chips = Array.prototype.slice.call(document.querySelectorAll('[data-filtro-cat]'));
    const formFiltro = filtroCategoria.closest('form');

    function pintarChips() {
        chips.forEach(function (chip) {
            const activo = chip.value === String(filtroCategoria.value || '');
            chip.classList.toggle('border-emerald-600', activo);
            chip.classList.toggle('bg-emerald-600', activo);
            chip.classList.toggle('text-white', activo);
            chip.classList.toggle('border-gray-200', !activo);
            chip.classList.toggle('bg-white', !activo);
            chip.classList.toggle('text-gray-600', !activo);
            chip.classList.toggle('hover:border-emerald-300', !activo);
        });
    }

    chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            filtroCategoria.value = chip.value;
            pintarChips();
            if (formFiltro) { formFiltro.submit(); }
        });
    });

    pintarChips();
</script>
@endpush