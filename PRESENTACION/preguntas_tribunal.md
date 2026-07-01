# Preguntas del Tribunal — Sistema Web-Móvil de Reciclaje "Zona Sur"

## A. Base de datos (integridad y diseño)

1. **¿Por qué no repetiste al menos 3 tests comparando contra MySQL (que es tu BD objetivo) en vez de SQLite?**  
   *Guía: SQLite en memoria acelera el CI y garantiza aislamiento entre tests; el mapeo es el mismo Eloquent para ambos. El `verificar_sistema.sh` corre la regresión contra MySQL real. Hoy los 91 tests pasan montados en SQLite (×N HMs).*

2. **¿Una oferta "en proceso" puede venderse otra vez por un segundo comprador?**  
   *La UNIQUE en `transacciones.oferta_id` + `lockForUpdate` la bloquea; el segundo comprador recibe `oferta_no_disponible` (código estable) bajo transacción pesimista.*

3. **¿Qué pasa si el comprobante se imprime dos veces?**  
   *Idempotente: el correlativo `CMP-AAAA-####` y el registro se generan una sola vez; la segunda descarga reutiliza el mismo, sin duplicar en BD (y la vista solo borra un archivo ya existente).*

4. **¿Qué pasa si un usuario es eliminado con ofertas activas?**  
   *Soft-delete / integridad: las ofertas quedan ligadas al ciudadano; se decide no borrar físicamente para conservar trazabilidad (auditoría).*

5. **¿Cómo garantizas consistencia entre `ofertas` y `transacciones`?**  
   *Transacción SQL con `lockForUpdate`, verificación de estado y actualización de la oferta a `completada` en el mismo commit.*

## B. Seguridad y escalabilidad

6. **¿Cómo evitas que alguien lea el comprobante o las transacciones de otra empresa?**  
   *507/403: por contrato los controladores web solo listan lo del `auth_user()`, y la API filtra por dueño. Cobertura anti-IDOR verificada con tests.*

7. **Escalabilidad: si crecen las ofertas en toda la ciudad, ¿qué limitante encontraste?**  
   *MySQL con las consultas espaciales y geográficas indexadas; la caja de Zona Sur reduce el universo. Aceptando índice geoespacial simple, el punto débil honesto es no usar PostGIS.*

8. **¿Por qué el rate limiting se hace por email y no por IP?**  
   *Clientes detrás de un hotspot/NAT comparten IP; limitar por IP rompería la demo a los jurados. El email es la identidad correcta.*

9. **¿Qué haces ante un ataque de fuerza bruta al login del admin?**  
   *6 intentos/min por email → `TooManyRequests 429`; contraseña bcrypt 12 rondas + sesiones HTTP con CSRF.*

## C. Funcionamiento del flujo (lógica de negocio)

10. **¿Cómo se determina "completada" una oferta?**  
    *Solo vía transacción finalizada: peso real de balanza × precio → estado `completada` en el mismo commit. "Pendiente/Disponible" significa publicada y sin comprar.*

11. **Si la empresa compra 3 kg pero la oferta era de 10, ¿se liberan los 7 restantes?**  
    *En el modelo actual la oferta es indivisible (1 oferta → 1 compra). Los 7 kg seguirían sin ser re-pujados; se menciona como punto futuro con diálogo de candidatos y prioridad (Rome), o se podría permitir parcialidas. Acepta la honestidad.*

12. **¿Qué pasa si el ciudadano no lleva el material que prometió en la oferta?**  
    *El peso lo mide la balanza de la empresa al recibir; el monto se recalcula con peso real. El UX no penaliza automáticamente (mejora futura: reputación/penalización de ciudadanos).*

13. **¿Por qué el precio es opcional (`precio_acordado_kg`)?**  
    *Por diseño de negocio: el precio de referencia de la categoría es el default; el acordado pone a la empresa en libertad, pero sin tope — se topa contra la referencia (admitir como flanco defendible).*

## D. Pruebas (calidad y verificación)

14. **¿Qué probás y qué no en los 91 tests?**  
    *31 flujos: login/roles, ofertas (crear/solicitar/cancelar/geovalidar), compras (exito, no-disponible, doble, venta ajeno), comprobantes (idempotencia), admin (empresas/categorías). No requiero UI manual, el `verificar_sistema.sh` juega la regresión contra MySQL con la app real.*

15. **¿Cómo corrés los tests?**  
    *`php artisan test` con PHPUnit 12.5 en SQLite en memoria; además `verificar_sistema.sh` recorre los roles vía HTTP real.*

16. **¿Qué pasa si los jurados hacen doble click en "Comprar"?**  
    *La UNIQUE + lock pesimista devuelve el error; en pantalla se bloquea el botón tras la primera petición.*

## E. PWA / móvil / demo en vivo

17. **¿Por qué PWA y no app Expo?**  
    *Por ser un MVP en presentación: instalar sin tienda, cache offline, actualización automática en teléfonos del jurado, sin build nativo ni Google Play. El prototipo Expo quedó `_archive/mobile/`. Si el jurado insiste en nativa, el mismo Laravel puede servir la API Bearer.*

18. **¿Cómo se actualiza la PWA instalada?**  
    *`sw.js` versión fija (`reciclaje-v2`) + network-first: cuando el navegador detecta el nuevo `manifest`, hace cache invalidation; una recarga resuelve.*

19. **¿Qué pasa si el internet del hotspot muere durante la demo?**  
    *Plan B: cada celular se conecta al `localhost:8000` del Mac en la misma red Wi-Fi; la PWA offline cachea lo leído y muestra fallback mientras se restablece el túnel.*

20. **¿Cuáles son las URLs y el QR?**  
    *Principal y respaldo `*.trycloudflare.com` + `logs/url_principal_qr.png` (watchdog `check_publico.sh` cada 20 s). El QR se escanea y entra directo a la demo.*

## F. Lógica geográfica y negocios

21. **¿Cómo validás que la oferta está dentro de la Zona Sur?**  
    *Caja geográfica (lat/lon) del área; si se sale, la API la rechaza con error de geovalidación (aunque en la demo los celulares están dentro del rango).*

22. **¿Para qué sirve el radio de búsqueda en km?**  
    *Filtra ofertas cercanas a la empresa con Haversine (y círculo en Leaflet), mejorando la pertinencia y el rendimiento de la búsqueda.*

23. **¿Cuántos días es válida una oferta?**  
    *Por defecto indefinida hasta cancelarse/expirar; se explicita la mejora futura: TTL por categoría y decaimiento de relevancia.*

## G. Gestión del proyecto y de riesgos

24. **¿Cómo se divide el trabajo si es multiusuario?**  
    *Roles claros: admin (aprueba empresas/categorías), ciudadano (oferta), empresa (compra). Las empresas pendientes no operan hasta aprobación de admin.*

25. **¿Qué riesgos principales identificaste?**  
    *(a) dependencia del internet del hotspot — mitiga con respaldo local; (b) URLs temporales de trycloudflare — regeneración manual si muere; (c) servidor dev — declarado como demo; (d) credenciales en scripts — refactor a variables de entorno.*

## H. Despliegue y operación

26. **¿A qué apunta para producción?**  
    *PHP-FPM + Nginx + HTTPS real + colas (jobs) para PDF/notificaciones + dominio propio para el túnel nombrado; redis para sesiones/cache si escala.*

27. **¿Qué comando se ejecuta el día de la demo?**  
    *`./scripts/levantar_todo.sh --caffeinate` levanta MySQL, servidores, túneles, verificación, QR.*

28. **¿Qué pasa si se cae un túnel y el otro también?**  
    *El `check_publico.sh` detecta y loguea; se regenera con `./scripts/tunel.sh` (URL nueva) y se lee el QR nuevo; el fallback local siempre funciona.*