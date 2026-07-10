# Checklist del día — Presentación del Sistema de Reciclaje "Zona Sur"

## Antes (day -1)
- [ ] Confirmar internet del celular-hotspot (4G con datos).
- [ ] `./scripts/levantar_todo.sh --caffeinate` y dejar corriendo.
- [ ] Abrir `logs/url_principal_qr.png` y escanear con los 3 teléfonos del jurado (modo incógnito si es necesario).
- [ ] En cada teléfono: "Agregar a pantalla de inicio" → probar que abre la PWA standalone.
- [ ] Revisar cuentas demo: `admin@reciclaje.bo/admin123456`, `ciudadano@reciclaje.bo/ciudadano123`, `empresa@reciclaje.bo/empresa123456`.

## Ronda de prueba (los 3 celulares)
- [ ] **Admin**: aprobar una empresa de prueba nueva (crearla en otro celular) → verificar que pasa a activa.
- [ ] **Ciudadano**: publicar oferta con GPS real + foto pequeña → aparece en mapa/listado.
- [ ] **Empresa**: buscar por filtros/radio, comprar, descargar el comprobante PDF.
- [ ] **Admin**: ver el reporte global (PDF), revisar transacciones.
- [ ] Certificar que el botón "Comprar" no se rompe con doble click (protección anti-doble).

## Día de la presentación (abrir en 60 s)
- [ ] `./scripts/levantar_todo.sh --caffeinate` (si no está vivo: `verificar_sistema.sh`).
- [ ] Escanear QR nuevo si cambió la URL (watchdog puede haberlo regenerado).
- [ ] Abrir en el proyector: mapa con una oferta visible en tiempo real.
- [ ] Limpiar datos de estado (usar cuentas demo frescas si la ronda de prueba ensució datos) o explicar que son datos de demo.

## Contingencias
- [ ] **Túnel cayó**: `./scripts/tunel.sh 8000 principal` (URL nueva) y re-leer QR.
- [ ] **Internet muerto**: hotspot Wi-Fi del Mac + `localhost:8000` en los celulares (plan B).
- [ ] **Errores "TooManyRequests"**: esperar ~60 s (rate-limit 6/min).
- [ ] **Teléfono sin GPS/Zona Sur**: usar una coordenada válida dentro de la caja o el emulador.

## Material de defensa listo
- [ ] `resumen_tecnico.md` (resumen del sistema).
- [ ] `preguntas_tribunal.md` (28 preguntas con respuestas guía).
- [ ] `respuestas_30s.md` (versión corta por pregunta).
- [ ] Saber de memoria: stack, anti-doble compra, idempotencia de comprobante, planes de producción y flancos honestos.