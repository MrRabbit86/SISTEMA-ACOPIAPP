<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Comprobante {{ $comprobante->numero_comprobante }}</title>
    <style>
        @page { margin: 18px 22px; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            color: #1f2937;
            font-size: 11px;
            margin: 0;
        }
        .header-banner {
            background: #064e3b;
            color: #ffffff;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 12px;
        }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; }
        .header-logo { height: 34px; display: block; margin-bottom: 4px; }
        .header-sub { font-size: 10px; color: #a7f3d0; }
        .header-right { text-align: right; }
        .header-title { font-size: 15px; font-weight: bold; margin: 0 0 2px; color: #ffffff; }
        .header-meta { font-size: 10px; color: #d1fae5; line-height: 1.4; }
        .grid-2 { width: 100%; border-collapse: separate; border-spacing: 10px 0; margin: 0 -10px 10px; }
        .grid-2 > tbody > tr > td { width: 50%; vertical-align: top; }
        .panel {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 9px 12px;
            margin-bottom: 10px;
            background: #ffffff;
        }
        .panel h2 {
            margin: 0 0 6px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #065f46;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 4px;
        }
        table.kv { width: 100%; border-collapse: collapse; }
        table.kv td { padding: 2.5px 0; font-size: 10.5px; vertical-align: top; }
        table.kv td.k { color: #6b7280; width: 42%; }
        table.kv td.v { text-align: right; font-weight: 600; color: #111827; width: 58%; }
        table.detalle { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.detalle th, table.detalle td {
            border: 1px solid #e5e7eb;
            padding: 6px 7px;
            text-align: left;
            font-size: 10px;
        }
        table.detalle th { background: #ecfdf5; color: #065f46; font-weight: 700; }
        table.detalle td.num, table.detalle th.num { text-align: right; }
        .monto-box {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 6px;
            padding: 9px 14px;
            text-align: right;
            margin-bottom: 10px;
        }
        .monto-label { font-size: 10px; color: #065f46; text-transform: uppercase; letter-spacing: .4px; }
        .monto-valor { font-size: 20px; font-weight: bold; color: #047857; margin: 2px 0 0; }
        .pie {
            margin-top: 8px;
            text-align: center;
            color: #6b7280;
            font-size: 9.5px;
            border-top: 1px solid #e5e7eb;
            padding-top: 7px;
            line-height: 1.4;
        }
    </style>
</head>
<body>
@php
    $oferta = $transaccion->oferta;
    $empresaUser = $transaccion->empresa;
    $empresaPerfil = $empresaUser?->empresa;
    $ciudadano = $oferta?->ciudadano;
    $ciudadanoUser = $ciudadano?->usuario;

    $logoPath = public_path('images/navbar-logo.png');
    $logoBase64 = is_file($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : null;

    $pesoEstimado = (float) ($oferta?->cantidad_estimada_kg ?? 0);
    $pesoReal = (float) $transaccion->peso_real_kg;
    $diffPeso = round($pesoReal - $pesoEstimado, 2);
    $pctPeso = $pesoEstimado > 0 ? round(($diffPeso / $pesoEstimado) * 100, 1) : null;

    $precioRef = (float) ($oferta?->categoria?->precio_referencia_kg ?? 0);
    $precioAcordado = (float) $transaccion->precio_acordado_kg;
    $diffPrecio = round($precioAcordado - $precioRef, 2);

    $tiempoRespuesta = '—';
    if ($oferta?->fecha_publicacion && $transaccion->fecha_transaccion) {
        $minutos = (int) $oferta->fecha_publicacion->diffInMinutes($transaccion->fecha_transaccion, false);
        if ($minutos >= 0) {
            $horas = intdiv($minutos, 60);
            $mins = $minutos % 60;
            $tiempoRespuesta = $horas > 0 ? "{$horas} h {$mins} min" : "{$mins} min";
        }
    }
@endphp

<div class="header-banner">
    <table class="header-table">
        <tr>
            <td>
                @if ($logoBase64)
                    <img src="{{ $logoBase64 }}" class="header-logo" alt="ACOPIAPP">
                @else
                    <strong style="font-size: 16px;">ACOPIAPP COCHABAMBA</strong>
                @endif
                <div class="header-sub">Control de compra-venta y trazabilidad de residuos sólidos</div>
            </td>
            <td class="header-right">
                <div class="header-title">Comprobante de compra · N.º {{ $comprobante->numero_comprobante }}</div>
                <div class="header-meta">
                    Fecha de compra: {{ $transaccion->fecha_transaccion?->format('d/m/Y H:i') }}<br>
                    Emitido: {{ $comprobante->fecha_emision?->format('d/m/Y H:i') }}
                </div>
            </td>
        </tr>
    </table>
</div>

<table class="grid-2">
    <tr>
        <td>
            <div class="panel" style="margin-bottom: 0;">
                <h2>Empresa acopiadora</h2>
                <table class="kv">
                    <tr><td class="k">Nombre</td><td class="v">{{ $empresaUser?->name ?? '—' }}</td></tr>
                    <tr><td class="k">Dirección</td><td class="v">{{ $empresaPerfil?->direccion ?? $empresaUser?->direccion ?? '—' }}</td></tr>
                    <tr><td class="k">Teléfono</td><td class="v">{{ $empresaUser?->telefono ?? '—' }}</td></tr>
                    <tr><td class="k">Correo</td><td class="v">{{ $empresaUser?->email ?? '—' }}</td></tr>
                </table>
            </div>
        </td>
        <td>
            <div class="panel" style="margin-bottom: 0;">
                <h2>Vendedor (ciudadano)</h2>
                <table class="kv">
                    <tr><td class="k">Nombre</td><td class="v">{{ $ciudadanoUser?->name ?? '—' }}</td></tr>
                    <tr><td class="k">C.I.</td><td class="v">{{ $ciudadano?->ci ?? '—' }}</td></tr>
                    <tr><td class="k">Zona</td><td class="v">{{ $ciudadano?->zona ?? 'Zona Sur' }}</td></tr>
                    <tr><td class="k">Teléfono</td><td class="v">{{ $ciudadanoUser?->telefono ?? '—' }}</td></tr>
                </table>
            </div>
        </td>
    </tr>
</table>

<div class="panel">
    <h2>Trazabilidad logística y métricas operativas</h2>
    <table class="kv">
        <tr>
            <td class="k">Oferta de origen</td>
            <td class="v">#{{ $oferta?->id ?? '—' }} · Publicada: {{ $oferta?->fecha_publicacion?->format('d/m/Y H:i') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="k">Modalidad de entrega</td>
            <td class="v">{{ $oferta?->modalidadLabel() ?? '—' }}</td>
        </tr>
        <tr>
            <td class="k">Ubicación (GPS)</td>
            <td class="v">{{ $oferta ? number_format((float) $oferta->latitud, 6).', '.number_format((float) $oferta->longitud, 6) : '—' }}</td>
        </tr>
        <tr>
            <td class="k">Tiempo de cierre</td>
            <td class="v">{{ $tiempoRespuesta }}</td>
        </tr>
        <tr>
            <td class="k">Puntos verdes otorgados</td>
            <td class="v">+{{ number_format((int) ($transaccion->puntos_otorgados ?? 0)) }} pts</td>
        </tr>
    </table>
</div>

<table class="detalle">
    <thead>
        <tr>
            <th>Material</th>
            <th class="num">Estimado (kg)</th>
            <th class="num">Peso real (kg)</th>
            <th class="num">Desv. peso</th>
            <th class="num">Precio ref. (Bs/kg)</th>
            <th class="num">Acordado (Bs/kg)</th>
            <th class="num">Var. precio</th>
            <th class="num">Total (Bs)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>{{ $oferta?->categoria?->nombre ?? '—' }}</strong></td>
            <td class="num">{{ number_format($pesoEstimado, 2) }}</td>
            <td class="num"><strong>{{ number_format($pesoReal, 2) }}</strong></td>
            <td class="num">
                {{ ($diffPeso >= 0 ? '+' : '').number_format($diffPeso, 2) }} kg
                @if ($pctPeso !== null)
                    ({{ ($pctPeso >= 0 ? '+' : '').number_format($pctPeso, 1) }}%)
                @endif
            </td>
            <td class="num">{{ number_format($precioRef, 2) }}</td>
            <td class="num">{{ number_format($precioAcordado, 2) }}</td>
            <td class="num">
                @if (abs($diffPrecio) < 0.005)
                    En ref.
                @else
                    {{ ($diffPrecio >= 0 ? '+' : '').number_format($diffPrecio, 2) }} Bs
                @endif
            </td>
            <td class="num"><strong>{{ number_format((float) $transaccion->monto_total, 2) }}</strong></td>
        </tr>
    </tbody>
</table>

<div class="monto-box">
    <div class="monto-label">Monto total de la transacción</div>
    <p class="monto-valor">Bs {{ number_format((float) $transaccion->monto_total, 2) }}</p>
</div>

<div class="pie">
    El presente comprobante documenta la transacción de compra-venta de material reciclable en Cochabamba.<br>
    Transacción #{{ $transaccion->id }} · Oferta #{{ $oferta?->id ?? '—' }} · Comprobante {{ $comprobante->numero_comprobante }}
</div>
</body>
</html>
