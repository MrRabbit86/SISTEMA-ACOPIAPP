@extends('layouts.app')

@section('title', 'Listado de ofertas')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Listado de ofertas</h1>
        <p class="text-sm text-gray-500">Filtre por material, estado y distancia desde un punto.</p>
    </div>

    <div class="mb-6 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
        <form method="GET" action="{{ route('ofertas.listado') }}" class="grid grid-cols-2 gap-4 md:grid-cols-5">
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
                <a href="{{ route('ofertas.listado') }}"
                   class="ml-2 rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr class="text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                    <th class="px-4 py-3">Material</th>
                    <th class="px-4 py-3">Cantidad (kg)</th>
                    <th class="px-4 py-3">Entrega</th>
                    <th class="px-4 py-3">Zona</th>
                    <th class="px-4 py-3">Coordenadas</th>
                    <th class="px-4 py-3">Publicado</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($ofertas as $oferta)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if ($oferta->foto_url)
                                    <img src="{{ asset('storage/'.e($oferta->foto_url)) }}" alt="Foto"
                                         class="h-10 w-10 rounded-md object-cover ring-1 ring-gray-200">
                                @else
                                    <x-material-icon :categoria="$oferta->categoria"/>
                                @endif
                                <div>
                                    <p class="font-medium">{{ $oferta->categoria?->nombre }}</p>
                                    @if ($oferta->descripcion)
                                        <p class="max-w-[14rem] text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($oferta->descripcion, 50) }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ $oferta->cantidad_estimada_kg }}</td>
                        <td class="px-4 py-3 text-xs">{{ $oferta->modalidadLabel() }}</td>
                        <td class="px-4 py-3">{{ $oferta->ciudadano?->zona ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500">
                            {{ $oferta->latitud }}, {{ $oferta->longitud }}
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $oferta->fecha_publicacion?->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <x-estado-badge :estado="$oferta->estado->value" :label="$oferta->estado->label()" />
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('ofertas.show', $oferta) }}"
                               class="text-emerald-700 hover:underline">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-gray-500">
                            <p class="text-3xl">🔍</p>
                            <p class="mt-2">No hay ofertas que coincidan con los filtros.</p>
                            <a href="{{ route('ofertas.listado') }}" class="mt-1 inline-block text-sm font-medium text-emerald-700 hover:underline">Limpiar filtros</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

@push('scripts')
    <script>
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