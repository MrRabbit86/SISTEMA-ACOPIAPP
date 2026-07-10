# Respuestas en 30 segundos — Sistema Web-Móvil de Reciclaje "Zona Sur"

## Arquitectura / stack
1. **¿Stack?** PHP 8.5 + Laravel 13 (MVC/API), MySQL 8, Blade + Tailwind 4 + Vite, Leaflet para mapas, PWA instalable, dompdf para comprobantes.
2. **¿Patrón en servicios?** Result (DTO) con códigos de error estables, enumeraciones tipadas para roles/estados, middleware `role:` para RBAC.
3. **¿Móvil?** PWA: sin tienda, cache offline y auto-actualización; el prototipo Expo quedó archivado, el mismo Laravel sirve la API Bearer.

## Base de datos
4. **¿Por qué SQLite en tests?** Velocidad y aislamiento; Eloquent es idéntico y `verificar_sistema.sh` corre la regresión contra MySQL real.
5. **¿Doble compra?** UNIQUE en `transacciones.oferta_id` + `lockForUpdate`: el 2º comprador recibe `oferta_no_disponible`.
6. **¿Comprobante dos veces?** Idempotente: mismo correlativo `CMP-AAAA-####`, no duplica registros.

## Seguridad
7. **¿Ataque al panel?** Bcrypt 12 rondas, CSRF, sesiones HTTP, rate-limit 6/min por email p/ fuerza bruta.
8. **¿Rate-limit por email y no por IP?** Los celulares comparten IP del hotspot; email es la identidad correcta.
9. **¿IDOR?** 403/507 si lo pide quien no es dueño; verificado con tests.

## Negocio / flujo
10. **¿"Completada"?** Solo vía transacción finalizada (peso real de balanza × precio) en el mismo commit.
11. **¿Compra parcial?** Hoy 1 oferta = 1 compra; parcialidas es mejora futura (y 2º comprador ya está protegido).
12. **¿Precio?** Default es el precio de referencia; el acordado es opcional — flanco sin tope a admitir.

## Pruebas
13. **¿91 tests?** 31 flujos por rol (login, ofertas, compras, comprobantes, admin) + regresión HTTP real contra MySQL con `verificar_sistema.sh`.

## Demo / PWA
14. **¿QR/URLs?** Principal y respaldo `trycloudflare.com` visibles en `logs/`, watchdog de 20 s, plan B local `localhost:8000`.
15. **¿Actualización PWA?** `sw.js` fijado (`reciclaje-v2`) con network-first; refrescar resuelve.

## Producción
16. **¿Proyección?** PHP-FPM + Nginx + HTTPS real + colas para PDF/notificaciones + dominio propio para el túnel nombrado.