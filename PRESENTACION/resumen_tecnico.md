# Resumen Técnico — Sistema Web-Móvil de Reciclaje "Zona Sur" (Cochabamba)

## 1. ¿Qué hace el sistema?

Plataforma que conecta la **oferta** de residuos reciclables (ciudadanos) con la
**demanda** (empresas acopiadoras) en la Zona Sur de Cochabamba, con trazabilidad de
compra-venta y acreditación económica en **puntos verdes**.

**Flujo funcional de punta a punta:**

1. **Ciudadano** se registra (queda **activo al instante**) y publica ofertas de material
   con **geolocalización**, categoría, peso estimado (kg), descripción, modalidad de
   entrega (recojo a domicilio / punto verde) y foto opcional (≤ 2 MB).
2. **Empresa acopiadora** se registra → queda en estado **pendiente** hasta que el
   **admin** la apruebe. Luego explora ofertas en **mapa Leaflet** o **listado**
   (filtros: material, estado, radio en km) y registra la **compra** con peso real en
   balanza (+ precio acordado opcional).
3. El servidor calcula el **monto** (`peso × precio`), marca la oferta **completada**,
   otorga **puntos verdes** (10 por kg vendido, `floor(peso × 10)`) y emite un
   **comprobante PDF** correlativo.
4. **Admin**: aprueba/rechaza empresas, gestiona categorías de material y sus precios
   de referencia.
5. **Reportes** (empresa: solo los suyos; admin: todos) con exportación PDF e historial.

## 2. Stack y tecnologías (versiones reales del proyecto)

| Capa | Tecnología | Detalle |
|---|---|---|
| Backend | **PHP 8.5 / Laravel 13** (`laravel/framework ^13.17`) | MVC, API REST + panel web |
| API | **Laravel Sanctum 4.3** | Tokens Bearer para el cliente móvil |
| Base de datos | **MySQL 8** (`sistema_reciclaje_2026`), InnoDB, utf8mb4 | ACID, integridad referencial |
| PDF | **barryvdh/laravel-dompdf 3.1** | Comprobantes y reportes |
| Frontend | **Blade + Tailwind CSS 4 + Vite 8** (assets con hash) | Renderizado servidor |
| Mapas | **Leaflet + OpenStreetMap** (CDN) | Mapa de ofertas, círculos de radio |
| PWA | **manifest.webmanifest + `sw.js`** (cache `reciclaje-v2`, network-first) | Instalable, standalone, iconos con PHP GD |
| Móvil | **PWA instalable**; app **Expo/React Native archivada** en `_archive/mobile/` | Decisión: frontend único web/PWA |
| Pruebas | **PHPUnit 12.5**, SQLite en memoria | 91 tests / 268 aserciones |
| Despliegue demo | `php -S` (dev server) en `:8000`/`:8001` + **Cloudflare Tunnel** (`cloudflared`, HTTP/2, IPv4) | HTTPS público sin dominio |

## 3. Arquitectura y organización del código

- **Patrón Result (DTO):** `app/Support/Result.php` (`ok()`/`fail()` con códigos de
  error estables, p. ej. `oferta_no_disponible`) usado por los services.
- **Lógica en servicios** (`OfertaService`, `CompraService`, `UsuarioService`,
  `ComprobanteService`, `ReporteService`); controladores delgados separados en
  `App\Http\Controllers\Api` y `...\Web`.
- **Enumeraciones tipadas:** `Rol`, `EstadoOferta`, `EstadoTransaccion`,
  `EstadoUsuario`, `ModalidadEntrega` (estados no como strings sueltos).
- **Geolocalización:** `app/Support/Geolocalizacion.php` — caja de la Zona Sur
  (lat −17.5…−17.2, lon −66.3…−66.0) y **Haversine** para radios.
- **Autenticación dual:** sesiones HTTP para el panel web; Sanctum Bearer para `/api/*`.
- **RBAC:** middleware `role:` (`EnsureRol`).

## 4. Base de datos (10+ tablas)

`users` · `ciudadanos` · `empresas_acopiadoras` · `categorias_material` · `ofertas` ·
`transacciones` · `comprobantes` · `reportes` · `personal_access_tokens` (Sanctum) ·
`cache`/`jobs`.

Relaciones clave:
- `ofertas.ciudadano_id → ciudadanos` (quién publica).
- `transacciones.oferta_id` con restricción **UNIQUE** → una oferta solo se compra una vez.
- `transacciones.empresa_id → empresas_acopiadoras` (quién compra).
- `comprobantes.transaccion_id` → correlativo `CMP-AAAA-####` idempotente.

## 5. Seguridad y robustez (argumentos de defensa)

- Contraseñas **Bcrypt (12 rondas)**.
- **Anti fuerza bruta por cuenta:** `RateLimiter` `login.web` (6/min) y `login.api`
  (5/min), llave por *email* (no por IP compartida) en `AppServiceProvider`.
- **Anti doble-compra:** `DB::transaction` + `SELECT … FOR UPDATE`
  (`lockForUpdate`) sobre la oferta + verificación de estado. Concurrencia pesimista.
- **Comprobante idempotente:** segunda descarga reutiliza el mismo correlativo sin
  duplicar registro en BD.
- **Anti-duplicados de oferta:** una categoría por ciudadano cada 24 h (`OfertaService`).
- **Autorización por recurso:** dueño cancela / ve su comprobante; empresa solo sus
  transacciones (403 si es ajeno). Protección **IDOR** verificada.
- **CSRF** en web, tokens Bearer en API; Blade escapa salidas (XSS); validación con
  `FormRequest` (GPS, pesos, precios).
- **`trustProxies('*')`** → feo pero controlado: URL y esquema (https) correctos tras
  el túnel; no afecta identidad.
- Servidores arrancados con `display_errors=0 log_errors=1` → sin "Broken pipe" en
  pantallas.

## 6. Despliegue de la presentación

- `scripts/levantar_todo.sh [--caffeinate]`: MySQL → servidores :8000/:8001 → túneles
  Cloudflare (HTTP/2, reconexión conserva URL) → verificación → **QR regenerado** +
  URLs impresas.
- `scripts/tunel.sh <puerto> <etiqueta>`: crea un túnel + QR.
- `scripts/check_publico.sh 20`: vigila URLs cada 20 s (sin reiniciar = no cambia URL).
- `scripts/verificar_sistema.sh`: regresión integral por roles contra MySQL real.

### Estado de referencia (cuentas demo)

| Rol | Email | Contraseña |
|---|---|---|
| Admin | admin@reciclaje.bo | admin123456 |
| Ciudadano | ciudadano@reciclaje.bo | ciudadano123 |
| Empresa | empresa@reciclaje.bo | empresa123456 |

## 7. Puntos débiles honestos (mejora pendiente)

1. Servidor `php artisan serve` (dev) en la demo → producción: PHP-FPM + Nginx, HTTPS
   con dominio real, colas para PDF/notificaciones.
2. `trustProxies('*')` abierto → restringir a las IPs del proxy real.
3. `precio_acordado_kg` sin tope → topar con el precio de referencia de la categoría.
4. `APP_DEBUG=true` y credenciales en `.env`/scripts → `APP_DEBUG=false` en producción,
   credenciales dedicadas fuera del repo.
5. Demo depende del internet del celular-hotspot → plan B local (`localhost:8000`).