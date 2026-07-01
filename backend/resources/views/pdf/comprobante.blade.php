<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Comprobante {{ $comprobante->numero_comprobante }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Arial, sans-serif; color: #1f2937; font-size: 12px; margin: 0; }
        .encabezado { border-bottom: 3px solid #047857; padding-bottom: 10px; margin-bottom: 16px; }
        .encabezado h1 { margin: 0; font-size: 18px; color: #047857; }
        .encabezado .numero { float: right; text-align: right; }
        .encabezado .numero strong { display: block; font-size: 14px; }
        .panel { border: 1px solid #d1d5db; border-radius: 6px; padding: 10px 14px; margin-bottom: 14px; }
        .panel h2 { margin: 0 0 8px; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #6b7280; }
        table.detalle { width: 100%; border-collapse: collapse; }
        table.detalle th, table.detalle td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        table.detalle th { background: #e7f5ef; color: #065f46; }
        table.detalle td.total { text-align: right; }
        .monto { font-size: 22px; font-weight: bold; color: #047857; text-align: right; margin: 0; }
        .fila { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .fila dt { color: #4b5563; }
        .fila dd { text-align: right; font-weight: 600; margin: 0; }
        .pie { margin-top: 20px; text-align: center; color: #9ca3af; font-size: 10px; border-top: 1px solid #e5e7eb; padding-top: 8px; }
        .clearfix { clear: both; }
    </style>
</head>
<body>
    <div class="encabezado">
        <div class="numero">
            <strong>N.º {{ $comprobante->numero_comprobante }}</strong>
            Emitido: {{ $comprobante->fecha_emision?->format('d/m/Y H:i') }}
        </div>
        <h1>Comprobante de compra</h1>
        <div>Reciclaje Zona Sur · Control de compra-venta de residuos sólidos</div>
        <div class="clearfix"></div>
    </div>

    <div class="panel">
        <h2>Empresa compradora</h2>
        <dl class="fila"><dt>Razón social</dt><dd>{{ $transaccion->empresa?->empresa?->razon_social ?? $transaccion->empresa?->name }}</dd></dl>
        <dl class="fila"><dt>NIT</dt><dd>{{ $transaccion->empresa?->empresa?->nit ?? '—' }}</dd></dl>
        <dl class="fila"><dt>Encargado</dt><dd>{{ $transaccion->empresa?->name }}</dd></dl>
    </div>

    <div class="panel">
        <h2>Vendedor (ciudadano)</h2>
        <dl class="fila"><dt>Nombre</dt><dd>{{ $transaccion->oferta?->ciudadano?->usuario?->name }}</dd></dl>
        <dl class="fila"><dt>C.I.</dt><dd>{{ $transaccion->oferta?->ciudadano?->ci ?? '—' }}</dd></dl>
        <dl class="fila"><dt>Zona</dt><dd>{{ $transaccion->oferta?->ciudadano?->zona ?? 'Zona Sur' }}</dd></dl>
        <dl class="fila"><dt>Teléfono</dt><dd>{{ $transaccion->oferta?->ciudadano?->usuario?->telefono ?? '—' }}</dd></dl>
    </div>

    <table class="detalle">
        <thead>
            <tr>
                <th>Material</th>
                <th>Peso real (kg)</th>
                <th>Precio (Bs/kg)</th>
                <th class="total">Total (Bs)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $transaccion->oferta?->categoria?->nombre }}</td>
                <td>{{ number_format((float) $transaccion->peso_real_kg, 2) }}</td>
                <td>{{ number_format((float) $transaccion->precio_acordado_kg, 2) }}</td>
                <td class="total"><strong>{{ number_format((float) $transaccion->monto_total, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <p class="monto">Monto total: Bs {{ number_format((float) $transaccion->monto_total, 2) }}</p>

    <div class="pie">
        El presente comprobante documenta la transacción de compra-venta de material reciclable en la Zona Sur de Cochabamba. ·
        Transacción #{{ $transaccion->id }} · Fecha: {{ $transaccion->fecha_transaccion?->format('d/m/Y H:i') }}
    </div>
</body>
</html>