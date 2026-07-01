<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaccion;
use App\Support\ComprobanteService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ComprobanteController extends Controller
{
    public function descargar(Transaccion $transaccion, Request $request): Response
    {
        $user = $request->user();

        $donoLaOferta = $transaccion->loadMissing('oferta.ciudadano')->oferta?->ciudadano?->usuario_id === $user->id;

        abort_unless(
            $user->esAdmin() || ($user->esEmpresa() && $transaccion->empresa_id === $user->id) || ($user->esCiudadano() && $donoLaOferta),
            403,
            'No tiene permisos para descargar este comprobante.',
        );

        $servicio = new ComprobanteService();
        $comprobante = $servicio->generar($transaccion);
        $pdf = $servicio->render($transaccion, $comprobante);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$comprobante->numero_comprobante.'.pdf"',
        ]);
    }
}