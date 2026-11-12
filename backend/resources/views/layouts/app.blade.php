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
    <link rel="icon" href="/icons/icon-192.png" type="image/png">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <title>@yield('title', 'Sistema de Reciclaje') · Panel</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-gray-100 min-h-screen font-sans text-gray-900">
    @if (auth()->check())
        <nav class="bg-emerald-900 text-white shadow">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3">
                <div class="flex items-center gap-6">
                    <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center transition hover:opacity-90">
                        <img src="{{ asset('images/navbar-logo.png') }}" alt="ACOPIAPP Cochabamba" class="h-10 w-auto">
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
                    <div class="relative" id="notif-wrapper">
                        <button type="button" id="notif-bell"
                                class="relative rounded-md p-2 text-emerald-100 hover:bg-emerald-800 focus:outline-none"
                                aria-label="Notificaciones">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span id="notif-badge"
                                  class="hidden absolute -right-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">0</span>
                        </button>
                        <div id="notif-panel"
                             class="hidden absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-lg bg-white text-gray-800 shadow-xl ring-1 ring-black/10">
                            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-2.5">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Notificaciones</span>
                                <button type="button" id="notif-leer-todas"
                                        class="text-xs font-medium text-emerald-700 hover:underline">
                                    Marcar leídas
                                </button>
                            </div>
                            <div id="notif-list" class="max-h-80 divide-y divide-gray-100 overflow-y-auto text-xs">
                                <p class="px-4 py-6 text-center text-gray-400">Cargando...</p>
                            </div>
                        </div>
                    </div>
                    <span class="hidden sm:inline text-emerald-100">{{ auth()->user()->name }}
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

    <x-pwa-install-banner />
    <x-validacion-numerica />

    @if (auth()->check())
        <div id="toast-container" class="pointer-events-none fixed right-4 top-16 z-50 flex w-full max-w-sm flex-col gap-2"></div>
        <script>
            (function () {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const storageKey = 'acopiapp_notif_seen_{{ auth()->id() }}';
                const bell = document.getElementById('notif-bell');
                const panel = document.getElementById('notif-panel');
                const badge = document.getElementById('notif-badge');
                const list = document.getElementById('notif-list');
                const leerTodasBtn = document.getElementById('notif-leer-todas');
                const toastContainer = document.getElementById('toast-container');

                function showToast(titulo, mensaje, url) {
                    if (!toastContainer) return;
                    const toast = document.createElement('div');
                    toast.className = 'pointer-events-auto rounded-lg bg-emerald-900 p-3.5 text-white shadow-lg ring-1 ring-emerald-700 transition';
                    toast.innerHTML = '';

                    const titleEl = document.createElement('p');
                    titleEl.className = 'text-xs font-semibold text-emerald-200';
                    titleEl.textContent = titulo;

                    const msgEl = document.createElement('p');
                    msgEl.className = 'mt-0.5 text-xs text-white';
                    msgEl.textContent = mensaje;

                    toast.appendChild(titleEl);
                    toast.appendChild(msgEl);

                    if (url) {
                        toast.style.cursor = 'pointer';
                        toast.addEventListener('click', () => { window.location.href = url; });
                    }

                    toastContainer.appendChild(toast);
                    setTimeout(() => toast.remove(), 7000);
                }

                @if (session('status'))
                    showToast('Confirmación', @json(session('status')));
                @endif

                function escapeText(str) {
                    const div = document.createElement('div');
                    div.textContent = str ?? '';
                    return div.innerHTML;
                }

                function getSeenSet() {
                    try {
                        const raw = sessionStorage.getItem(storageKey);
                        return raw ? new Set(JSON.parse(raw)) : null;
                    } catch {
                        return null;
                    }
                }

                function saveSeenSet(set) {
                    try {
                        sessionStorage.setItem(storageKey, JSON.stringify(Array.from(set).slice(-60)));
                    } catch {}
                }

                async function cargarNotificaciones() {
                    try {
                        const res = await fetch('{{ route('notificaciones.recientes') }}', {
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin',
                        });
                        if (!res.ok) return;
                        const data = await res.json();

                        const noLeidas = Number(data.no_leidas || 0);
                        if (badge) {
                            badge.textContent = noLeidas > 9 ? '9+' : String(noLeidas);
                            badge.classList.toggle('hidden', noLeidas === 0);
                        }

                        const items = Array.isArray(data.items) ? data.items : [];
                        const seen = getSeenSet();

                        if (seen === null) {
                            saveSeenSet(new Set(items.map((i) => i.id)));
                        } else {
                            let changed = false;
                            items.forEach((item) => {
                                if (!seen.has(item.id)) {
                                    seen.add(item.id);
                                    changed = true;
                                    if (!item.leida) {
                                        showToast(item.titulo, item.mensaje, item.url);
                                    }
                                }
                            });
                            if (changed) saveSeenSet(seen);
                        }

                        if (!list) return;
                        if (items.length === 0) {
                            list.innerHTML = '<p class="px-4 py-6 text-center text-gray-400">Sin notificaciones aún.</p>';
                            return;
                        }

                        list.innerHTML = items.map((item) => `
                            <a href="/notificaciones/${encodeURIComponent(item.id)}/leer"
                               data-notif-id="${escapeText(item.id)}"
                               data-notif-url="${escapeText(item.url)}"
                               class="block px-4 py-3 hover:bg-emerald-50 ${item.leida ? 'bg-white text-gray-500' : 'bg-emerald-50/60 font-medium text-gray-900'}">
                                <div class="flex items-start justify-between gap-2">
                                    <span class="text-emerald-800">${escapeText(item.titulo)}</span>
                                    ${item.leida ? '' : '<span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-emerald-500"></span>'}
                                </div>
                                <p class="mt-0.5 text-gray-600">${escapeText(item.mensaje)}</p>
                                <span class="mt-1 block text-[10px] text-gray-400">${escapeText(item.fecha)}</span>
                            </a>
                        `).join('');

                        list.querySelectorAll('a[data-notif-id]').forEach((link) => {
                            link.addEventListener('click', async (e) => {
                                e.preventDefault();
                                const id = link.getAttribute('data-notif-id');
                                const targetUrl = link.getAttribute('data-notif-url') || '{{ route('dashboard') }}';
                                await fetch(`/notificaciones/${encodeURIComponent(id)}/leer`, {
                                    method: 'POST',
                                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                                    credentials: 'same-origin',
                                });
                                window.location.href = targetUrl;
                            });
                        });
                    } catch {}
                }

                bell?.addEventListener('click', (e) => {
                    e.stopPropagation();
                    panel?.classList.toggle('hidden');
                });

                document.addEventListener('click', (e) => {
                    if (panel && !panel.contains(e.target) && e.target !== bell) {
                        panel.classList.add('hidden');
                    }
                });

                leerTodasBtn?.addEventListener('click', async () => {
                    await fetch('{{ route('notificaciones.leer-todas') }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });
                    cargarNotificaciones();
                });

                cargarNotificaciones();
                setInterval(cargarNotificaciones, 15000);
            })();
        </script>
    @endif

    @stack('scripts')
</body>
</html>