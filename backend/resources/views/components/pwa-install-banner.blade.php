<div id="pwa-install-banner" class="hidden fixed bottom-4 right-4 left-4 z-50 mx-auto max-w-md rounded-xl border border-emerald-200 bg-white p-4 shadow-xl sm:left-auto">
    <div class="flex items-start gap-3">
        <img src="/icons/icon-192.png" alt="AcopiaApp" class="h-10 w-10 shrink-0 rounded-lg border border-emerald-100">
        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-gray-900">Instala AcopiaApp en tu celular</p>
            <p id="pwa-install-desc" class="mt-0.5 text-xs text-gray-600">
                Accede más rápido desde tu pantalla de inicio.
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
                <button type="button" id="pwa-install-btn"
                        class="rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-600">
                    Instalar app
                </button>
                <button type="button" id="pwa-install-close"
                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50">
                    Ahora no
                </button>
            </div>
        </div>
    </div>
</div>

<div id="pwa-ios-modal" class="hidden fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-4 sm:items-center">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <div class="flex items-center gap-2.5">
                <img src="/icons/icon-192.png" alt="AcopiaApp" class="h-8 w-8 rounded-lg">
                <h3 class="text-base font-semibold text-gray-900">Instalar AcopiaApp en iPhone / iPad</h3>
            </div>
            <button type="button" id="pwa-ios-close" class="rounded-md px-2 py-1 text-sm text-gray-500 hover:bg-gray-100" aria-label="Cerrar">✕</button>
        </div>
        <ol class="mt-4 space-y-3 text-sm text-gray-700">
            <li class="flex items-start gap-3">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-800">1</span>
                <span>Abre esta página en <strong>Safari</strong> (en iOS solo Safari permite instalar aplicaciones web).</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-800">2</span>
                <span>Toca el botón <strong>Compartir</strong> (cuadro con flecha hacia arriba) en la barra inferior de Safari.</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-800">3</span>
                <span>Desliza hacia abajo y selecciona <strong>«Agregar a pantalla de inicio»</strong>.</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-800">4</span>
                <span>Toca <strong>Agregar</strong> arriba a la derecha y abre <strong>AcopiaApp</strong> desde tu inicio.</span>
            </li>
        </ol>
        <button type="button" id="pwa-ios-done"
                class="mt-5 w-full rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-600">
            Entendido
        </button>
    </div>
</div>

<script>
    (() => {
        const CLAVE_OCULTAR = 'acopiapp_pwa_banner_oculto';
        const yaInstalada = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        if (yaInstalada || localStorage.getItem(CLAVE_OCULTAR) === '1') {
            return;
        }

        const banner = document.getElementById('pwa-install-banner');
        const btnInstalar = document.getElementById('pwa-install-btn');
        const btnCerrar = document.getElementById('pwa-install-close');
        const modalIos = document.getElementById('pwa-ios-modal');
        const btnIosCerrar = document.getElementById('pwa-ios-close');
        const btnIosListo = document.getElementById('pwa-ios-done');
        const desc = document.getElementById('pwa-install-desc');

        if (!banner || !btnInstalar || !btnCerrar) return;

        const esIos = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        let eventoInstalacion = null;

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            eventoInstalacion = e;
            banner.classList.remove('hidden');
        });

        if (esIos) {
            if (desc) {
                desc.textContent = 'Agrega AcopiaApp a tu pantalla de inicio en 4 pasos desde Safari.';
            }
            btnInstalar.textContent = 'Ver pasos';
            banner.classList.remove('hidden');
        }

        btnInstalar.addEventListener('click', async () => {
            if (eventoInstalacion) {
                eventoInstalacion.prompt();
                await eventoInstalacion.userChoice;
                eventoInstalacion = null;
                banner.classList.add('hidden');
                return;
            }
            if (esIos && modalIos) {
                modalIos.classList.remove('hidden');
            }
        });

        const ocultarBanner = () => {
            banner.classList.add('hidden');
            localStorage.setItem(CLAVE_OCULTAR, '1');
        };

        btnCerrar.addEventListener('click', ocultarBanner);
        if (btnIosCerrar && modalIos) btnIosCerrar.addEventListener('click', () => modalIos.classList.add('hidden'));
        if (btnIosListo && modalIos) btnIosListo.addEventListener('click', () => {
            modalIos.classList.add('hidden');
            ocultarBanner();
        });
    })();
</script>
