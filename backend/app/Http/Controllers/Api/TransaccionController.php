<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransaccionRequest;
use App\Http\Resources\TransaccionResource;
use App\Models\Oferta;
use App\Models\Transaccion;
use App\Services\CompraService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransaccionController extends Controller
{
    public function store(TransaccionRequest $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user->esEmpresa() && $user->estaAprobado(), 403,
            'Solo las empresas aprobadas pueden registrar transacciones.');

        $validated = $request->validated();

        $oferta = Oferta::query()->with('categoria')->findOrFail($validated['oferta_id']);

        $resultado = (new CompraService())->registrar($user, $oferta, $validated);

        if ($resultado->mal()) {
            return response()->json(['message' => $resultado->mensaje()], 422);
        }

        $transaccion = $resultado->valor();

        return response()->json([
            'message' => 'Transacción registrada. La oferta fue marcada como completada.',
            'data' => new TransaccionResource($transaccion->load([
                'oferta.categoria', 'oferta.ciudadano.usuario', 'empresa.empresa',
            ])),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user->esEmpresa() || $user->esAdmin(), 403,
            'Solo las empresas y el administrador pueden consultar transacciones.');

        $query = Transaccion::query()
            ->with(['oferta.categoria', 'oferta.ciudadano.usuario', 'empresa.empresa'])
            ->latest('fecha_transaccion');

        if ($user->esEmpresa()) {
            $query->where('empresa_id', $user->id);
        }

        if ($request->filled('oferta_id')) {
            $query->where('oferta_id', $request->integer('oferta_id'));
        }

        if ($request->filled('desde')) {
            $query->whereDate('fecha_transaccion', '>=', $request->query('desde'));
        }

        if ($request->filled('hasta')) {
            $query->whereDate('fecha_transaccion', '<=', $request->query('hasta'));
        }

        return response()->json([
            'data' => TransaccionResource::collection($query->get()),
        ]);
    }

    public function show(Transaccion $transaccion, Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user->esAdmin() || ($user->esEmpresa() && $transaccion->empresa_id === $user->id), 403,
            'No tiene permisos para ver esta transacción.');

        return response()->json([
            'data' => new TransaccionResource($transaccion->load([
                'oferta.categoria', 'oferta.ciudadano.usuario', 'empresa.empresa',
            ])),
        ]);
    }

    public function mias(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user->esCiudadano() && $user->ciudadano, 403,
            'Solo los ciudadanos pueden consultar sus ventas.');

        $transacciones = Transaccion::query()
            ->with(['oferta.categoria', 'oferta.ciudadano.usuario', 'empresa.empresa'])
            ->whereHas('oferta', fn ($query) => $query->where('ciudadano_id', $user->ciudadano->id))
            ->latest('fecha_transaccion')
            ->get();

        return response()->json([
            'data' => TransaccionResource::collection($transacciones),
        ]);
    }
}