@extends('layouts.app')

@section('title', 'Gestión de usuarios')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Usuarios</h1>
            <p class="text-sm text-gray-500">Valide las cuentas registradas desde la app móvil.</p>
        </div>
    </div>

    <div class="mb-6 rounded-lg bg-white p-4 shadow">
        <form method="GET" action="{{ route('admin.usuarios') }}" class="grid grid-cols-2 gap-4 md:grid-cols-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Estado</label>
                <select name="estado" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <option value="">Todos</option>
                    <option value="pendiente" @selected($filtro_estado === 'pendiente')>Pendientes</option>
                    <option value="aprobado" @selected($filtro_estado === 'aprobado')>Aprobados</option>
                    <option value="rechazado" @selected($filtro_estado === 'rechazado')>Rechazados</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Rol</label>
                <select name="rol" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <option value="">Todos</option>
                    <option value="ciudadano" @selected($filtro_rol === 'ciudadano')>Ciudadano</option>
                    <option value="empresa" @selected($filtro_rol === 'empresa')>Empresa</option>
                    <option value="admin" @selected($filtro_rol === 'admin')>Admin</option>
                </select>
            </div>
            <div class="self-end">
                <button type="submit"
                        class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-600">
                    Filtrar
                </button>
                <a href="{{ route('admin.usuarios') }}"
                   class="ml-2 rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    <div class="overflow-x-auto rounded-lg bg-white shadow ring-1 ring-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr class="text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                    <th class="px-4 py-3">Nombre</th>
                    <th class="px-4 py-3">Correo</th>
                    <th class="px-4 py-3">Rol</th>
                    <th class="px-4 py-3">Perfil</th>
                    <th class="px-4 py-3">Registro</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($usuarios as $usuario)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $usuario->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $usuario->email }}</td>
                        <td class="px-4 py-3 capitalize">{{ $usuario->rol->value }}</td>
                        <td class="px-4 py-3 text-gray-600">
                            @if ($usuario->esCiudadano())
                                {{ $usuario->ciudadano?->zona ?? '—' }}
                            @elseif ($usuario->esEmpresa())
                                {{ $usuario->empresa?->razon_social }}
                            @else
                                Sistema
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $usuario->created_at?->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            <x-estado-badge :estado="$usuario->estado->value" :label="ucfirst($usuario->estado->value)" />
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($usuario->estado->value === 'pendiente')
                                <form method="POST" action="{{ route('admin.usuarios.aprobar', $usuario) }}" class="inline">
                                    @csrf
                                    <button class="rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-500">
                                        Aprobar
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.usuarios.rechazar', $usuario) }}" class="inline">
                                    @csrf
                                    <button class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-500">
                                        Rechazar
                                    </button>
                                </form>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">No hay usuarios.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $usuarios->links() }}
    </div>
@endsection