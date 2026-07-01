<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Reporte;
use App\Support\ReporteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReporteController extends Controller
{
    public function index(Request $request): View
    {
        $desde = $request->filled('desde') ? $request->query('desde') : null;
        $hasta = $request->filled('hasta') ? $request->query('hasta') : null;
        $empresaId = auth()->user()->esEmpresa() ? auth()->id() : null;

        $datos = (new ReporteService())->resumen($desde, $hasta, $empresaId);

        return view('reportes.index', [
            'datos' => $datos,
            'desde' => $desde,
            'hasta' => $hasta,
            'historial' => Reporte::query()
                ->latest('fecha_generacion')
                ->limit(20)
                ->get(),
        ]);
    }

    public function exportar(Request $request): Response
    {
        $desde = $request->filled('desde') ? $request->query('desde') : null;
        $hasta = $request->filled('hasta') ? $request->query('hasta') : null;
        $empresaId = auth()->user()->esEmpresa() ? auth()->id() : null;

        $servicio = new ReporteService();
        $datos = $servicio->resumen($desde, $hasta, $empresaId);
        $servicio->registrar('resumen_pdf', [
            'desde' => $desde,
            'hasta' => $hasta,
            'empresa_id' => $empresaId,
        ], $datos);

        $pdf = Pdf::loadView('pdf.reporte', ['datos' => $datos])->setPaper('a4');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="reporte-'.now()->format('Y-m-d').'.pdf"',
        ]);
    }
}