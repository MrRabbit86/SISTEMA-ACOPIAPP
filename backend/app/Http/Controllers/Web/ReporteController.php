<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReporteRequest;
use App\Models\Reporte;
use App\Support\ReporteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\View;

class ReporteController extends Controller
{
    public function index(Request $request): View|Response|JsonResponse
    {
        $validacion = $this->validarFechas($request);
        if ($validacion instanceof Response || $validacion instanceof JsonResponse) {
            return $validacion;
        }

        [$desde, $hasta] = $validacion;
        $empresaId = auth()->user()->esEmpresa() ? auth()->id() : null;

        $datos = (new ReporteService())->resumen($desde, $hasta, $empresaId);

        return view('reportes.index', [
            'datos' => $datos,
            'desde' => $desde,
            'hasta' => $hasta,
            'historial' => $this->historialReciente(),
        ]);
    }

    public function exportar(Request $request): Response|JsonResponse
    {
        $validacion = $this->validarFechas($request);
        if ($validacion instanceof Response || $validacion instanceof JsonResponse) {
            return $validacion;
        }

        [$desde, $hasta] = $validacion;
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

    /**
     * Valida los filtros de fecha y devuelve [desde, hasta] o una respuesta HTTP 422.
     *
     * @return array{0: ?string, 1: ?string}|Response|JsonResponse
     */
    private function validarFechas(Request $request): array|Response|JsonResponse
    {
        $entrada = [
            'desde' => $request->filled('desde') ? $request->query('desde') : null,
            'hasta' => $request->filled('hasta') ? $request->query('hasta') : null,
        ];

        $formRequest = new ReporteRequest();
        $validator = Validator::make($entrada, $formRequest->rules(), $formRequest->messages());

        if (! $validator->fails()) {
            return [$entrada['desde'], $entrada['hasta']];
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $empresaId = auth()->user()->esEmpresa() ? auth()->id() : null;
        $datosVacios = (new ReporteService())->resumen(null, null, $empresaId);
        $errorBag = (new ViewErrorBag())->put('default', $validator->errors());

        return response()->view('reportes.index', [
            'datos' => $datosVacios,
            'desde' => $entrada['desde'],
            'hasta' => $entrada['hasta'],
            'historial' => $this->historialReciente(),
            'errors' => $errorBag,
        ], 422);
    }

    private function historialReciente()
    {
        return Reporte::query()
            ->latest('fecha_generacion')
            ->limit(20)
            ->get();
    }
}