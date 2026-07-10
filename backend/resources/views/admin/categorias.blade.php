@extends('layouts.app')

@section('title', 'Categorías de material')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Categorías de material</h1>
        <p class="text-sm text-gray-500">Administre los materiales reciclables y sus precios de referencia.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-lg bg-white p-6 shadow ring-1 ring-gray-200">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">Nueva categoría</h2>
            <form method="POST" action="{{ route('admin.categorias.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-medium">Nombre</label>
                    <input type="text" name="nombre" required maxlength="50"
                           class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Descripción</label>
                    <textarea name="descripcion" rows="2"
                              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"></textarea>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Precio de referencia (Bs/kg)</label>
                    <input type="number" step="0.01" min="0.01" name="precio_referencia_kg" required
                           class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>
                <button type="submit"
                        class="w-full rounded-md bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-600">
                    Guardar categoría
                </button>
            </form>
        </div>

        <div class="overflow-x-auto rounded-lg bg-white shadow ring-1 ring-gray-200 lg:col-span-2">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Descripción</th>
                        <th class="px-4 py-3">Precio (Bs/kg)</th>
                        <th class="px-4 py-3 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($categorias as $categoria)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ $categoria->nombre }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $categoria->descripcion ?? '—' }}</td>
                            <td class="px-4 py-3">{{ number_format((float) $categoria->precio_referencia_kg, 2) }}</td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('admin.categorias.destroy', $categoria) }}"
                                      onsubmit="return confirm('¿Eliminar esta categoría?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-600 hover:underline">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">No hay categorías.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection