<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de actividad</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Arial, sans-serif; color: #1f2937; font-size: 11px; margin: 0; }
        .encabezado { border-bottom: 3px solid #047857; padding-bottom: 8px; margin-bottom: 14px; }
        .encabezado h1 { margin: 0; font-size: 17px; color: #047857; }
        .encabezado .rango { float: right; text-align: right; color: #4b5563; }
        .tarjetas { width: 100%; margin-bottom: 14px; }
        .tarjetas td { border: 1px solid #d1d5db; border-radius: 4px; padding: 8px 10px; width: 25%; }
        .tarjetas .lbl { font-size: 9px; text-transform: uppercase; color: #6b7280; }
        .tarjetas .val { font-size: 16px; font-weight: bold; color: #047857; }
        h2.seccion { font-size: 12px; text-transform: uppercase; letter-spacing: .5px; color: #065f46; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; margin: 18px 0 8px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; }
        table.data th { background: #e7f5ef; color: #065f46; }
        td.num, th.num { text-align: right; }
        .pie { margin-top: 16px; text-align: center; color: #9ca3af; font-size: 9px; border-top: 1px solid #e5e7eb; padding-top: 6px; }
    </style>
</head>
<body>
    <div class="encabezado">
        <div class="rango">
            Periodo:
            {{ $datos['rango']['desde'] ?? 'Inicio' }} al {{ $datos['rango']['hasta'] ?? 'actualidad' }}<br>
            Generado: {{ now()->format('d/m/Y H:i') }}
        </div>
        <h1>Reporte de compra-venta de residuos</h1>
        <div>Reciclaje Zona Sur · Cochabamba</div>
    </div>

    <table class="tarjetas">
        <tr>
            <td><div class="lbl">Ofertas totales</div><div class="val">{{ $datos['ofertas']['total'] }}</div></td>
            <td><div class="lbl">Activas</div><div class="val">{{ $datos['ofertas']['activas'] }}</div></td>
            <td><div class="lbl">Completadas</div><div class="val">{{ $datos['ofertas']['completadas'] }}</div></td>
            <td><div class="lbl">Canceladas</div><div class="val">{{ $datos['ofertas']['canceladas'] }}</div></td>
        </tr>
        <tr>
            <td><div class="lbl">Transacciones</div><div class="val">{{ $datos['transacciones']['total'] }}</div></td>
            <td><div class="lbl">Peso total (kg)</div><div class="val">{{ number_format($datos['transacciones']['peso_total_kg'], 2) }}</div></td>
            <td><div class="lbl">Monto total (Bs)</div><div class="val">{{ number_format($datos['transacciones']['monto_total'], 2) }}</div></td>
            <td><div class="lbl">Monto promedio (Bs)</div><div class="val">{{ number_format($datos['transacciones']['monto_promedio'], 2) }}</div></td>
        </tr>
    </table>

    <h2 class="seccion">Montos por material</h2>
    <table class="data">
        <thead>
            <tr><th>Material</th><th class="num">Transacciones</th><th class="num">Peso (kg)</th><th class="num">Precio prom. (Bs/kg)</th><th class="num">Monto (Bs)</th></tr>
        </thead>
        <tbody>
            @foreach ($datos['por_categoria'] as $categoria)
                <tr>
                    <td>{{ $categoria['material'] }}</td>
                    <td class="num">{{ $categoria['transacciones'] }}</td>
                    <td class="num">{{ number_format($categoria['peso_kg'], 2) }}</td>
                    <td class="num">{{ number_format($categoria['precio_promedio'], 2) }}</td>
                    <td class="num">{{ number_format($categoria['monto_total'], 2) }}</td>
                </tr>
            @endforeach
            @if (empty($datos['por_categoria']))
                <tr><td colspan="5" style="text-align:center;color:#9ca3af;">Sin datos en el periodo.</td></tr>
            @endif
        </tbody>
    </table>

    <h2 class="seccion">Transacciones por mes</h2>
    <table class="data">
        <thead><tr><th>Mes</th><th class="num">Transacciones</th><th class="num">Monto (Bs)</th></tr></thead>
        <tbody>
            @foreach ($datos['por_mes'] as $mes)
                <tr>
                    <td>{{ $mes['etiqueta'] }}</td>
                    <td class="num">{{ $mes['transacciones'] }}</td>
                    <td class="num">{{ number_format($mes['monto_total'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="pie">Reporte generado automáticamente por el Sistema Web-Móvil de Reciclaje de la Zona Sur de Cochabamba.</div>
</body>
</html>