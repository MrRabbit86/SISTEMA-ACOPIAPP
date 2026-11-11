<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReporteRequest;
use App\Support\ReporteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ReporteController extends Controller
{
    public function index(ReporteRequest $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user->esAdmin() || $user->esEmpresa(), 403,
            'Solo las empresas y el administrador pueden generar reportes.');

        $validated = $request->validated();
        $desde = $validated['desde'] ?? null;
        $hasta = $validated['hasta'] ?? null;
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

    public function pdf(ReporteRequest $request): Response
    {
        $user = $request->user();

        abort_unless($user->esAdmin() || $user->esEmpresa(), 403,
            'Solo las empresas y el administrador pueden exportar reportes.');

        $validated = $request->validated();
        $desde = $validated['desde'] ?? null;
        $hasta = $validated['hasta'] ?? null;
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