## Goal
- Completar el backend "Sistema Web‑Móvil de Reciclaje" (Laravel + MySQL + panel web) y entregarlo como **PWA instalable en navegador móvil** (decisión final), dejando la app Expo archivada.

## Constraints & Preferences
- Monorepo en `/Users/eledezma/Desktop/SISTEMA DE RECICLAJE/` (`backend/` = Laravel; `_archive/mobile/` = Expo archivada; `scripts/` = E2E).
- **MySQL root password: `3l3d3zm4F`**; BD activa `sistema_reciclaje_2026`.
- Cuentas demo intactas: admin@reciclaje.bo/admin123456, ciudadano@reciclaje.bo/ciudadano123, empresa@reciclaje.bo/empresa123456, pendiente@reciclaje.bo/pendiente123.
- Usuario pidió explícitamente: **web app en navegador sin descargar** → **PWA sobre el panel Laravel** (no Expo). Sin offline de datos ni push.
- App Expo construida → **archivada** (`_archive/mobile/`), no es entrega final.
- Ejecutar "paso a paso" y verificar cada avance (tests + E2E como fuente de verdad).
- **Patrón Result incorporado (decisión del usuario):** `App\Support\Result` + capa `App\Services` para operaciones de negocio, acotado a ofertas/compra/usuarios, sin refactor global. Objetivo: sistema estable y de calidad para tesis.

## Progress
### Done (sprints acumulados)
- **Registro web único ciudadano/empresa** (selector `tipo` en `login.blade.php`): ciudadano → APROBADO + auto-login → `/mis-ofertas`; empresa → PENDIENTE. `RegistroWebRequest` + `Web\RegistroController::store` ramifican por `tipo`.
- **API móvil (Fase A):** `/api/ofertas/mias`, `/api/mis-transacciones`, comprobante API abierto al ciudadano dueño (mismo criterio que web).
- **PWA implementada y verificada:** `scripts/generar_iconos.php` (iconos 192/512), `manifest.webmanifest` + ruta `/manifest.json`, `sw.js` (caparazón cache-first `/build/*`, network-first navegación, `/api/*` siempre red), registro en `resources/js/app.js`, metas PWA en layouts.
- **GPS robusto + botón minimalista (`mis-ofertas.blade.php`):** botón ancho completo, borde punteado esmeralda, pin SVG + spinner + estados (buscando→listo→error); pre-consulta de permisos; validación Zona Sur; **reintento automático**: señal fina (`enableHighAccuracy`, 10s) → si no hay, **ubicación por red** (`enableHighAccuracy:false, maximumAge:60000`) antes de fallar; mensajes por `error.code`.
- **Túnel estable:** `cloudflared` instalado (Homebrew) pero **la conexión IPv6 a Cloudflare falla en bucle** → detenido. Tuvo URL `https://thehun-acc-dpi-flip.trycloudflare.com`. Se quedó **localtunnel como túnel principal**. Contingencia: relanzar cloudflared con `--edge-ip-version 4`.
- **FASE 1 ESTABILIDAD + PATRÓN RESULT (completada):**
  - `app/Support/Result.php` (ok/fail/bien/mal/valor/mensaje/codigo, sin dependencias).
  - `app/Services/OfertaService` (`publicar` con `lockForUpdate` anti-duplicado y `existeDuplicada`; `cancelar`), `CompraService::registrar` (**`lockForUpdate` + check de estado → arregla el 500 por UNIQUE `transacciones.oferta_id` en doble compra**, devuelve `Result::fail('La oferta ya fue adquirida…')`), `UsuarioService` (`aprobar`/`rechazar`).
  - Refactor Web (`TransaccionController`→`CompraService`, `MiOfertaController`→`OfertaService`, `UsuarioController`→`UsuarioService`) y API (`Api\TransaccionController`, `Api\OfertaController`, `Api\AdminController`).
  - Compra doble: web → `back()->withErrors(['oferta'=>…])` (amigable, antes 500); API → `422` JSON.
  - Admin aprobar/rechazar web conserva `abort(422)` (el E2E lo exige como fuente de verdad).
  - `ComprobanteService::siguienteNumero` con `lockForUpdate` (numeración CMP correlativa segura).
  - `Oferta::guardarFoto` usa `guessExtension()` (no extensión del cliente).
  - `Api\OfertaController::index` filtra por defecto ofertas **activas** (PENDIENTE/EN_PROCESO), igual que la web.
  - **Anti fuerza bruta por cuenta:** `AppServiceProvider` registra `RateLimiter::for('login.web'|'login.api')` con `Limit::perMinute(6|5)->by(email)` (llave por email, no por IP compartida); rutas `/login` web y `/api/auth/login` usan `throttle:login.web|login.api`. (Primer intento con `throttle:6,1|5,1` por IP rompió el E2E con 46 fallos; corregido a por-cuenta → E2E 80/0.)
- **Verificación final:** **91 tests PHPUnit (268 aserciones)** verdes (nuevos: doble compra web+API, lista API excluye no-disponibles + filtro estado, límite de intentos web+API, `ResultTest` unit 3 casos); **E2E 80 PASS / 0 FAIL**; `php -l` OK en 15 archivos.
- **Túnel relanzado tras caída:** localtunnel pid 75616 → **nueva URL `https://tough-taxes-beg.loca.lt`** (verificada 200 en /login). Anterior `https://fifty-windows-cheat.loca.lt` murió (0 procesos).
- README actualizados (PWA + `_archive/mobile/`) en sprints previos.

### In Progress
- Prueba del usuario en el teléfono con la URL nueva (GPS + instalación PWA + recorrido demo): servicio localtunnel pid 75616 vivo (200).

### Blocked
- (none)

## Key Decisions
- **Entrega = PWA sobre el panel Laravel**; Expo archivada; sin offline/push.
- **Patrón Result acotado** a servicios núcleo (ofertas/compra/usuarios/comprobante); validación sigue en FormRequest; sin refactor global.
- Compra doble → error amigable web (antes 500); admin aprobar/rechazar conserva 422 (E2E es fuente de verdad).
- Anti fuerza bruta **por cuenta (email)**, no por IP compartida, para no bloquear flujos legítimos (ni al E2E).
- Túnel principal = localtunnel (cloudflared con IPv6 falla; relanzarlo exige `--edge-ip-version 4`).
- Instalación PWA requiere HTTPS (local o túnel).

## Next Steps
- Usuario prueba en teléfono: `https://tough-taxes-beg.loca.lt`; si tenía el PWA del host anterior, **desinstalar y reinstalar** (la URL cambió).
- Si localtunnel cae de nuevo: relanzar `npx --yes localtunnel --port 8000` o `cloudflared tunnel --url http://localhost:8000 --edge-ip-version 4`.
- Checklist tesis pendiente (sin código): deploy Hostinger (Web Hosting Premium/Business recomendado sobre VPS), documentación/manuales, respaldo BD, prueba final desde el teléfono. Opción gratuita: mkcert local + Oracle Always Free vs Hostinger.

## Critical Context
- Entorno: PHP 8.5.6, Composer 2.9, Node 20.20.2, MySQL (root/3l3d3zm4F), PHP GD y `sips` disponibles; servidor `php artisan serve --port=8000` (pid 26494, escucha 127.0.0.1:8000, responde 200); Mac LAN `192.168.1.16`; localtunnel no guarda subdominio fijo → la URL cambia en cada relanzamiento.
- `App\Support\Geolocalizacion` (Zona Sur: lat −17.5…−17.2, lng −66.3…−66.0, RADIO_MAX_KM=50).
- `transacciones.oferta_id` y `comprobantes.transaccion_id`/`numero_comprobante` son UNIQUE → por eso existían riesgos de 500 en doble compra/comprobante; ahora mitigados con `lockForUpdate`.
- E2E usa cuentas efímeras `*@probe.bo` con limpieza por FK; cuentas demo intactas.
- Laravel 13.17; `ThrottleRequests::$shouldHashKeys=true` → claves nombradas = `md5('<limiter>.<by>')`.
- Si se vuelve a `_archive/mobile/`, la descarga de PDF usa la **nueva API de `expo-file-system` (SDK 57)**: `File`/`Directory`/`Paths`, `File.downloadFileAsync` sin headers → usar `expo/fetch` con `Authorization` + `File.write(respuesta.bytes())`.

## Relevant Files
- `backend/app/Support/Result.php`, `app/Services/{OfertaService,CompraService,UsuarioService}.php` — patrón Result y reglas de negocio.
- `backend/app/Providers/AppServiceProvider.php` — limitadores `login.web` (6/min) y `login.api` (5/min) por email.
- `backend/routes/web.php` (login.store con `throttle:login.web`; `/manifest.json`) y `backend/routes/api.php` (`throttle:login.api`; `/api/ofertas/mias` antes del apiResource).
- `backend/app/Http/Controllers/{Web,Api}/...` — Transaccion, Oferta, MiOferta, Usuario, Admin → usan servicios + Result.
- `backend/app/Models/Oferta.php` (`guardarFoto` con `guessExtension`, `existeDuplicada`), `backend/app/Support/ComprobanteService.php` (`lockForUpdate`).
- `backend/resources/views/ciudadano/mis-ofertas.blade.php` — botón GPS rediseñado + reintento a red.
- `scripts/verificar_sistema.sh` — E2E por rol (actualmente **80 PASS / 0 FAIL**).
- `tests/Feature/{WebTest,TransaccionTest,OfertaTest,AuthTest}.php` y `tests/Unit/ResultTest.php` — 91 tests verdes.
- `scripts/generar_iconos.php`, `backend/public/{manifest.webmanifest,sw.js,icons/}` — PWA.
- `_archive/mobile/` — app Expo archivada.