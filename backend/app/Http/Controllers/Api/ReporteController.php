<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ReporteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReporteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user->esAdmin() || $user->esEmpresa(), 403,
            'Solo las empresas y el administrador pueden generar reportes.');

        $desde = $request->filled('desde') ? $request->query('desde') : null;
        $hasta = $request->filled('hasta') ? $request->query('hasta') : null;
        $empresaId = $user->esEmpresa() ? $user->id : null;

        $servicio = new ReporteService();
        $datos = $servicio->resumen($desde, $hasta, $empresaId);
        $servicio->registrar('resumen', [
            'desde' => $desde,
            'hasta' => $hasta,
            'empresa_id' => $empresaId,
        ], $datos);

        return response()->json($datos);
    }

    public function pdf(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user->esAdmin() || $user->esEmpresa(), 403,
            'Solo las empresas y el administrador pueden exportar reportes.');

        $desde = $request->filled('desde') ? $request->query('desde') : null;
        $hasta = $request->filled('hasta') ? $request->query('hasta') : null;
        $empresaId = $user->esEmpresa() ? $user->id : null;

        $servicio = new ReporteService();
        $datos = $servicio->resumen($desde, $hasta, $empresaId);
        $servicio->registrar('resumen_pdf', [
            'desde' => $desde,
            'hasta' => $hasta,
            'empresa_id' => $empresaId,
        ], $datos);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.reporte', [
            'datos' => $datos,
        ])->setPaper('a4');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="reporte-'.now()->format('Y-m-d').'.pdf"',
        ]);
    }
}