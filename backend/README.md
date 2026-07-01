# Sistema Web-Móvil de Reciclaje — Backend (API REST)

Sistema para el registro de ofertas de reciclaje y control de compra-venta de residuos sólidos en la Zona Sur de Cochabamba.

Diseñado para servir como API REST consumida por la **app móvil** (React Native/Expo, próxima iteración) y el **panel web** (Blade, siguiente sprint).

## Stack

- **Laravel 13** (PHP 8.x, patrón MVC) + **Laravel Sanctum** (tokens Bearer)
- **MySQL 8+** (InnoDB, integridad referencial ACID)
- Autenticación por roles: `admin`, `ciudadano`, `empresa`
- Encriptación de contraseñas con **Bcrypt** (12 rondas)
- API RESTful (JSON) con soporte CORS para clientes móviles

## Requisitos

- PHP ≥ 8.3 con extensiones `pdo_mysql`, `mbstring`
- Composer 2
- MySQL 8+ corriendo localmente
- Node 20+ (opcional, para `vite`)

## Instalación

```bash
cd backend

# 1. Variables de entorno
cp .env.example .env
# Editar .env y configurar la conexión MySQL:
#   DB_DATABASE=sistema_reciclaje_2026
#   DB_USERNAME=root
#   DB_PASSWORD=tu_password

# 2. Crear la base de datos (una sola vez)
mysql -u root -p -e "CREATE DATABASE sistema_reciclaje_2026 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 3. Dependencias, clave y migraciones
composer install
php artisan key:generate
php artisan migrate --seed

# 4. Servidor de desarrollo
php artisan serve
```

La API queda disponible en `http://localhost:8000/api` y el panel web en `http://localhost:8000`.

> Nota: el panel usa Tailwind compilado con Vite. Si los estilos no aparecen, ejecute `npm install && npm run build`.

## Cuentas de demostración (seeders)

| Rol | Email | Contraseña |
|-----|-------|------------|
| Administrador | `admin@reciclaje.bo` | `admin123456` |
| Ciudadano | `ciudadano@reciclaje.bo` | `ciudadano123` |
| Empresa | `empresa@reciclaje.bo` | `empresa123456` |
| Pendiente | `pendiente@reciclaje.bo` | `pendiente123` |

El registro se realiza desde la misma pantalla de login (pestaña *Registrarse*) mediante un **formulario único con selector de tipo**: *Ciudadano* (pide CI y zona; queda **activo de inmediato**, sin aprobación, y entra al panel) o *Empresa* (pide NIT y razón social; se crea con estado `pendiente`, no inicia sesión y el administrador debe aprobar la cuenta antes del primer inicio de sesión). Por API (`POST /api/auth/register`) el comportamiento es el mismo: ciudadano `aprobado`, empresa `pendiente`.

## Endpoints de la API

### Autenticación (`/api/auth`)
| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/api/auth/register` | Registro de ciudadano (queda **activo** al instante) o empresa (pendiente de aprobación) |
| POST | `/api/auth/login` | Inicio de sesión; devuelve token Bearer |
| POST | `/api/auth/logout` | Cierra sesión y revoca el token |
| GET | `/api/auth/me` | Datos del usuario autenticado |

### Catálogos (público)
| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/api/categorias` | Categorías de material con precio de referencia |

### Ofertas (requieren token)
| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/api/ofertas` | Lista ofertas. Filtros: `estado`, `categoria_id`, `lat`+`lng`+`radio` (km) |
| POST | `/api/ofertas` | Publica oferta (ciudadano aprobado). Campos: `categoria_id`, `cantidad_estimada_kg`, `latitud`, `longitud`, y opcionales `descripcion`, `modalidad_entrega` (`recojo_domicilio`/`entrega_punto_verde`) y `foto` (multipart, máx. 2 MB). Validación GPS de la Zona Sur y anti-duplicados |
| GET | `/api/ofertas/{id}` | Detalle de una oferta |
| GET | `/api/ofertas/mias` | Ofertas del ciudadano autenticado (para la app móvil) |
| POST | `/api/ofertas/{id}/cancelar` | Cancela una oferta (solo su dueño) |

### Transacciones (requieren rol `empresa` o `admin`)
| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/api/transacciones` | Registra una compra: `oferta_id`, `peso_real_kg` y `precio_acordado_kg` (opcional). El monto se calcula en el servidor (`peso × precio`); la oferta pasa a `completada` |
| GET | `/api/transacciones` | Lista transacciones. La empresa solo ve las suyas; el admin todas. Filtros: `oferta_id`, `desde`, `hasta` |
| GET | `/api/transacciones/{id}` | Detalle. Solo la empresa dueña o el admin |
| GET | `/api/mis-transacciones` | Ventas del ciudadano autenticado (para la app móvil) |

El precio de referencia de la categoría se usa como precio acordado cuando no se envía uno.

### Comprobantes y reportes (requieren token)
| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/api/transacciones/{id}/comprobante` | PDF del comprobante de la transacción (empresa dueña, admin o **ciudadano dueño de la oferta**). Generación idempotente con numeración correlativa `CMP-AAAA-####` |
| GET | `/api/reportes` | Estadísticas de ofertas y transacciones. Filtros: `desde`, `hasta`. La empresa solo ve las suyas; el admin, todo |
| GET | `/api/reportes/pdf` | Exporta el reporte en PDF |

El PDF del comprobante y del reporte se generan con **DomPDF** y quedan registrados en las tablas `comprobantes` y `reportes` (historial).

### Administración (requieren rol `admin`)
| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/api/admin/usuarios` | Lista usuarios. Filtros: `estado`, `rol`, `per_page` |
| POST | `/api/admin/usuarios/{id}/aprobar` | Aprueba una cuenta pendiente |
| POST | `/api/admin/usuarios/{id}/rechazar` | Rechaza una cuenta pendiente |

## Ejemplo de uso rápido (curl)

```bash
# 1. Registro como ciudadano (queda activo de inmediato; no necesita aprobación)
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"name":"Juan Perez","email":"juan@example.com","password":"secret1234",
       "rol":"ciudadano","ci":"1234567","zona":"Zona Sur"}'

# 2. (Solo empresas) El admin aprueba la cuenta (usar token del admin)
curl -X POST http://localhost:8000/api/admin/usuarios/1/aprobar \
  -H "Accept: application/json" -H "Authorization: Bearer TOKEN_ADMIN"

# 3. Login como ciudadano → obtiene token
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"email":"juan@example.com","password":"secret1234"}'

# 4. Publicar una oferta con geolocalización (Zona Sur de Cochabamba)
curl -X POST http://localhost:8000/api/ofertas \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -H "Authorization: Bearer TOKEN_CIUDADANO" \
  -d '{"categoria_id":2,"cantidad_estimada_kg":120.5,
       "latitud":-17.3932,"longitud":-66.1561}'

# 5. Empresa lista ofertas en un radio de 10 km
curl "http://localhost:8000/api/ofertas?lat=-17.3932&lng=-66.1561&radio=10" \
  -H "Accept: application/json" -H "Authorization: Bearer TOKEN_EMPRESA"
```

## Validación de geolocalización (Zona Sur de Cochabamba)

Las coordenadas de las ofertas se validan contra el rango de la Zona Sur:
- Latitud: entre `-17.50000000` y `-17.20000000`
- Longitud: entre `-66.30000000` y `-66.00000000`

Los filtros por radio usan la fórmula de **Haversine** (distancia en km).

## Estructura de datos

Entidades: `users`, `ciudadanos`, `empresas_acopiadoras`, `categorias_material`, `ofertas`, `transacciones`, `comprobantes`, `reportes`, `personal_access_tokens` (Sanctum). Todas con claves foráneas e integridad referencial.

## Panel web (Sprint 3)

Acceso por roles desde `http://localhost:8000`:

| Sección | Ruta | Acceso |
|---------|------|--------|
| Login | `/login` | Público; incluye la pestaña **Registrarse** con **formulario único de tipo Ciudadano/Empresa** (ciudadano activo al instante, empresa pendiente de aprobación) |
| Mapa de ofertas (Leaflet) | `/ofertas` | Empresa, Admin |
| Listado de ofertas | `/ofertas/listado` | Empresa, Admin |
| Detalle de oferta | `/ofertas/{id}` | Empresa, Admin |
| Transacciones | `/transacciones` | Empresa (solo las suyas), Admin |
| Reportes | `/reportes` | Empresa (solo las suyas), Admin |

En el detalle de una oferta disponible la empresa puede **registrar una compra** (peso real en balanza + precio acordado opcional); el monto se calcula en vivo y, al confirmar, la oferta queda `completada`. Las ofertas ya compradas muestran un resumen de la transacción y el botón **Descargar comprobante** (PDF).

La sección **Reportes** muestra tarjetas de resumen, montos por material, transacciones por mes y el historial de reportes generados, con filtro por período y botón **Exportar PDF**.
| Gestión de usuarios | `/admin/usuarios` | Admin |
| Categorías | `/admin/categorias` | Admin |
| Mis ofertas | `/mis-ofertas` | Ciudadano |

- **Publicación desde el panel:** en `Mis ofertas` el ciudadano publica material con categoría, peso aproximado, descripción breve, modalidad de entrega (recojo a domicilio / entrego en el punto verde), foto opcional y ubicación (geolocalización del navegador o coordenadas manuales). Las ofertas publicadas quedan activas de inmediato.
- **Puntos verdes:** por cada venta completada el ciudadano recibe **10 puntos por kg** vendido (`floor(peso × 10)`), visibles en `Mis ofertas` y en la API (`usuario.puntos`).
- **Mis ventas:** las ofertas `completadas` muestran la empresa compradora, peso real, monto, fecha y el botón **Descargar comprobante** (el ciudadano dueño puede descargar el PDF de su venta).
- **Mapa interactivo** con Leaflet + OpenStreetMap: marcadores por estado, popup con foto, descripción y modalidad; círculo del radio de búsqueda (filtros por material, estado y distancia en km desde un punto).
- La autenticación web usa **sesiones** (el cierre bloquea a cuentas pendientes/rechazadas, igual que la API).
- Los fondos de session flash muestran los resultados de aprobar/rechazar cuentas y eliminar categorías.

## PWA (instalable en el móvil)

El panel web es una **aplicación web dinámica** que se convierte en **PWA ligera**: funcione en el navegador del teléfono **sin descargar nada**, y permite añadirla a la pantalla de inicio como una app (pantalla completa, icono propio).

- `public/manifest.webmanifest` (`GET /manifest.json` con el MIME correcto) con `display: standalone`, `start_url: /dashboard` e iconos `192/512`.
- `public/sw.js` (service worker): precache del caparazón (manifest + iconos), `network-first` para la navegación con caché de assets de Vite; las llamadas `/api/*` **siempre van por red** (nada de datos de sesión obsoletos).
- Metas PWA en el layout y el login; iconos generados con `php scripts/generar_iconos.php backend/public/icons` (PHP GD).
- **Notas:** el service worker y la instalación requieren *contexto seguro* — `http://localhost:8000` funciona; desde un teléfono por IP LAN (`http://192.168.x.x:8000`) la web responsive funciona igual pero sin el instalable. Para PWA completa en el teléfono físico sirve el sistema por **HTTPS** (túnel ngrok/Cloudflare).

> La app nativa **Expo** que se exploró quedó **archivada** en `_archive/mobile/` (decidimos el frontend único web/PWA para la tesis).

## Verificación integral por roles (Sprint 6)

El script `../scripts/verificar_sistema.sh` prueba todos los usuarios/roles contra MySQL real (API + panel web), imprime **PASS/FAIL** por caso y deja la BD limpia (no toca las cuentas demo):

```bash
php artisan serve --port=8000 &
bash ../scripts/verificar_sistema.sh
```

Cubre: registro de cuentas (ciudadanos activos al instante, empresas pendientes) desde la API y desde el panel web por formulario único con tipo, bloqueo de pendientes/rechazadas, aprobación/rechazo de empresas (API y panel), CRUD de categorías, publicación de ofertas con GPS y anti-duplicados (API y panel web, con descripción, modalidad y foto), cancelaciones, compras con cálculo de monto, puntos verdes por kg vendido, comprobantes PDF correlativos e idempotentes (empresa y ciudadano dueño), aislamiento entre empresas y reportes JSON/PDF.

## Pruebas

Los tests usan SQLite en memoria (no tocan MySQL):

```bash
php artisan test
```

Cobertura: registro por rol (ciudadanos activos, empresas pendientes), login/aprobación, publicación de ofertas (API y panel web, con descripción, modalidad y foto), validación GPS y anti-duplicados, cancelación, puntos verdes por venta, filtros por radio, administración de cuentas, acceso al panel web por roles, registro/cálculo de transacciones, descarga de comprobantes PDF correlativos (empresa y ciudadano dueño) y generación de reportes con estadísticas.

## Próximos sprints

- **PWA sobre el panel web (implementada):** instalable en el móvil desde el navegador con icono propio y pantalla completa (ver sección PWA). La app **Expo** explorada quedó archivada en `_archive/mobile/`.
- **Despliegue:** documentación técnica del documento de tesis, respaldo de BD y pruebas finales.