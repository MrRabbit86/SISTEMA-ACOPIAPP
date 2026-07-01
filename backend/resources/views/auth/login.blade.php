<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#064e3b">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Reciclaje">
    <link rel="manifest" href="{{ route('manifest') }}">
    <link rel="icon" href="/images/logo.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <title>Iniciar sesión · Sistema de Reciclaje</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-emerald-900 font-sans text-gray-900">
    <div class="w-full max-w-md">
        <div class="mb-6 text-center text-white">
            <img src="{{ asset('images/login-logo.svg') }}" alt="Reciclaje Zona Sur"
                 class="mx-auto w-64 max-w-full rounded-xl bg-white px-4 py-3 shadow-xl">
            <p class="mt-3 text-sm text-emerald-200">Cochabamba · Registro de ofertas y compra-venta</p>
        </div>

        <div class="rounded-xl bg-white p-8 shadow-xl">
            <div class="mb-6 grid grid-cols-2 gap-1 rounded-lg bg-emerald-50 p-1">
                <button type="button" id="tab-login-btn" data-tab="login"
                        class="rounded-md px-4 py-2 text-sm font-medium transition {{ $errors->has('email') ? '' : 'bg-emerald-700 text-white' }}"
                        onclick="cambiarPestana('login')">Ingresar</button>
                <button type="button" id="tab-registro-btn" data-tab="registro"
                        class="rounded-md px-4 py-2 text-sm font-medium transition {{ $errors->has('email') ? 'bg-emerald-700 text-white' : '' }}"
                        onclick="cambiarPestana('registro')">Registrarse</button>
            </div>

            @if (session('status'))
                <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any() && ! $errors->has('email'))
                <div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200">
                    {{ $errors->first() }}
                </div>
            @endif

            <div id="panel-login" class="{{ $errors->has('email') ? 'hidden' : '' }}">
                <h2 class="mb-6 text-center text-xl font-semibold">Iniciar sesión</h2>

                @if ($errors->has('email'))
                    <div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200">
                        {{ $errors->first('email') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="email" class="mb-1 block text-sm font-medium">Correo electrónico</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                               class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="password" class="mb-1 block text-sm font-medium">Contraseña</label>
                        <input type="password" name="password" id="password" required
                               class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="remember" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            Recordarme
                        </label>
                    </div>
                    <button type="submit"
                            class="w-full rounded-md bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-600">
                        Ingresar
                    </button>
                </form>
            </div>

            <div id="panel-registro" class="{{ $errors->has('email') ? '' : 'hidden' }}">
                <h2 class="mb-6 text-center text-xl font-semibold">Crear cuenta</h2>

                <form method="POST" action="{{ route('registro.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="reg-tipo" class="mb-1 block text-sm font-medium">Tipo de cuenta</label>
                        <select name="tipo" id="reg-tipo" onchange="cambiarTipo()"
                                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            <option value="ciudadano" {{ old('tipo', 'ciudadano') === 'ciudadano' ? 'selected' : '' }}>Ciudadano</option>
                            <option value="empresa" {{ old('tipo') === 'empresa' ? 'selected' : '' }}>Empresa</option>
                        </select>
                    </div>
                    <div>
                        <label for="reg-name" class="mb-1 block text-sm font-medium">Nombre completo</label>
                        <input type="text" name="name" id="reg-name" value="{{ old('name') }}" required
                               class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="reg-email" class="mb-1 block text-sm font-medium">Correo electrónico</label>
                        <input type="email" name="email" id="reg-email" value="{{ old('email') }}" required
                               class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div id="campos-ciudadano" class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="reg-ci" class="mb-1 block text-sm font-medium">Carnet de identidad</label>
                            <input type="text" name="ci" id="reg-ci" value="{{ old('ci') }}"
                                   class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label for="reg-zona" class="mb-1 block text-sm font-medium">Zona</label>
                            <input type="text" name="zona" id="reg-zona" value="{{ old('zona') }}"
                                   class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        </div>
                    </div>
                    <div id="campos-empresa" class="hidden grid-cols-2 gap-3">
                        <div>
                            <label for="reg-nit" class="mb-1 block text-sm font-medium">NIT</label>
                            <input type="text" name="nit" id="reg-nit" value="{{ old('nit') }}"
                                   class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label for="reg-razon-social" class="mb-1 block text-sm font-medium">Razón social</label>
                            <input type="text" name="razon_social" id="reg-razon-social" value="{{ old('razon_social') }}"
                                   class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        </div>
                    </div>
                    <div>
                        <label for="reg-password" class="mb-1 block text-sm font-medium">Contraseña (mínimo 8 caracteres)</label>
                        <input type="password" name="password" id="reg-password" required
                               class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="reg-password-confirm" class="mb-1 block text-sm font-medium">Confirmar contraseña</label>
                        <input type="password" name="password_confirmation" id="reg-password-confirm" required
                               class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="reg-telefono" class="mb-1 block text-sm font-medium">Teléfono (opcional)</label>
                            <input type="text" name="telefono" id="reg-telefono" value="{{ old('telefono') }}"
                                   class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label for="reg-direccion" class="mb-1 block text-sm font-medium">Dirección (opcional)</label>
                            <input type="text" name="direccion" id="reg-direccion" value="{{ old('direccion') }}"
                                   class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        </div>
                    </div>
                    <button type="submit" id="reg-submit"
                            class="w-full rounded-md bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-600">
                        Crear cuenta y entrar
                    </button>
                    <p id="reg-nota" class="text-center text-xs text-gray-500">Al registrarse como ciudadano su cuenta queda activa de inmediato.</p>
                </form>
            </div>

            <p class="mt-6 text-center text-xs text-gray-500">
                Cuentas de demostración:<br>
                admin@reciclaje.bo · ciudadano@reciclaje.bo · empresa@reciclaje.bo
            </p>
        </div>
    </div>

    <script>
        function cambiarPestana(pestana) {
            const esLogin = pestana === 'login';

            document.getElementById('panel-login').classList.toggle('hidden', !esLogin);
            document.getElementById('panel-registro').classList.toggle('hidden', esLogin);

            const btnLogin = document.getElementById('tab-login-btn');
            const btnRegistro = document.getElementById('tab-registro-btn');

            btnLogin.classList.toggle('bg-emerald-700', esLogin);
            btnLogin.classList.toggle('text-white', esLogin);
            btnRegistro.classList.toggle('bg-emerald-700', !esLogin);
            btnRegistro.classList.toggle('text-white', !esLogin);
        }

        document.addEventListener('DOMContentLoaded', cambiarTipo);

        function cambiarTipo() {
            const tipo = document.getElementById('reg-tipo').value;
            const esEmpresa = tipo === 'empresa';

            document.getElementById('campos-ciudadano').classList.toggle('hidden', esEmpresa);
            document.getElementById('campos-empresa').classList.toggle('hidden', !esEmpresa);

            const ci = document.getElementById('reg-ci');
            const zona = document.getElementById('reg-zona');
            ci.required = !esEmpresa;
            zona.required = !esEmpresa;
            document.getElementById('reg-nit').required = esEmpresa;
            document.getElementById('reg-razon-social').required = esEmpresa;

            document.getElementById('reg-submit').textContent = esEmpresa
                ? 'Registrar empresa'
                : 'Crear cuenta y entrar';
            document.getElementById('reg-nota').textContent = esEmpresa
                ? 'Su cuenta de empresa quedará pendiente de aprobación por un administrador.'
                : 'Al registrarse como ciudadano su cuenta queda activa de inmediato.';
        }
    </script>
</body>
</html>
