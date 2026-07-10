<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#064e3b">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Reciclaje">
    <link rel="manifest" href="{{ route('manifest') }}">
    <link rel="icon" href="/images/logo.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <title>@yield('title', 'Sistema de Reciclaje') · Panel</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-gray-100 min-h-screen font-sans text-gray-900">
    @if (auth()->check())
        <nav class="bg-emerald-800 text-white shadow">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3">
                <div class="flex items-center gap-6">
                    <a href="{{ route('dashboard') }}" class="flex items-center rounded-lg bg-white px-2.5 py-1.5 shadow-sm transition hover:shadow">
                        <img src="{{ asset('images/logo.svg') }}" alt="Reciclaje Zona Sur" class="h-8 w-auto">
                    </a>
                    <div class="flex items-center gap-1 text-sm">
                        @if (auth()->user()->esEmpresa() || auth()->user()->esAdmin())
                            <a href="{{ route('ofertas.mapa') }}"
                               class="rounded-md px-3 py-1.5 {{ request()->routeIs('ofertas.mapa') ? 'bg-emerald-700' : 'hover:bg-emerald-700/60' }}">Mapa</a>
                            <a href="{{ route('ofertas.listado') }}"
                               class="rounded-md px-3 py-1.5 {{ request()->routeIs('ofertas.listado') ? 'bg-emerald-700' : 'hover:bg-emerald-700/60' }}">Listado</a>
                            <a href="{{ route('transacciones.index') }}"
                               class="rounded-md px-3 py-1.5 {{ request()->routeIs('transacciones.index') ? 'bg-emerald-700' : 'hover:bg-emerald-700/60' }}">Transacciones</a>
                            <a href="{{ route('reportes.index') }}"
                               class="rounded-md px-3 py-1.5 {{ request()->routeIs('reportes.index') ? 'bg-emerald-700' : 'hover:bg-emerald-700/60' }}">Reportes</a>
                        @endif
                        @if (auth()->user()->esAdmin())
                            <a href="{{ route('admin.usuarios') }}"
                               class="rounded-md px-3 py-1.5 {{ request()->routeIs('admin.usuarios') ? 'bg-emerald-700' : 'hover:bg-emerald-700/60' }}">Usuarios</a>
                            <a href="{{ route('admin.categorias') }}"
                               class="rounded-md px-3 py-1.5 {{ request()->routeIs('admin.categorias') ? 'bg-emerald-700' : 'hover:bg-emerald-700/60' }}">Categorías</a>
                        @endif
                        @if (auth()->user()->esCiudadano())
                            <a href="{{ route('mis-ofertas') }}"
                               class="rounded-md px-3 py-1.5 {{ request()->routeIs('mis-ofertas') ? 'bg-emerald-700' : 'hover:bg-emerald-700/60' }}">Mis ofertas</a>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <span class="text-emerald-100">{{ auth()->user()->name }}
                        <span class="text-emerald-300/80">({{ ucfirst(auth()->user()->rol->value) }})</span>
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-md bg-emerald-700 px-3 py-1.5 hover:bg-emerald-600">Salir</button>
                    </form>
                </div>
            </div>
        </nav>
    @endif

    <main class="mx-auto max-w-7xl px-4 py-6">
        @if (session('status'))
            <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200">
                {{ session('status') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200">
                <ul class="list-disc space-y-1 pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>