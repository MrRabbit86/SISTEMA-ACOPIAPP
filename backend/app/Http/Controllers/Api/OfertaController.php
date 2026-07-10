<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoOferta;
use App\Http\Controllers\Controller;
use App\Http\Requests\OfertaRequest;
use App\Http\Resources\OfertaResource;
use App\Models\Oferta;
use App\Services\OfertaService;
use App\Support\Geolocalizacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OfertaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Oferta::query()
            ->with(['ciudadano.usuario', 'categoria'])
            ->latest('fecha_publicacion');

        if ($request->filled('estado')) {
            $query->where('estado', $request->string('estado'));
        } else {
            $query->whereIn('estado', [
                EstadoOferta::PENDIENTE->value,
                EstadoOferta::EN_PROCESO->value,
            ]);
        }

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->integer('categoria_id'));
        }

        $ofertas = $query->get();

        if ($request->filled('lat') && $request->filled('lng') && $request->filled('radio')) {
            $radio = min((float) $request->query('radio'), Geolocalizacion::RADIO_MAX_KM);
            $lat = (float) $request->query('lat');
            $lng = (float) $request->query('lng');

            $ofertas = $ofertas->filter(function (Oferta $oferta) use ($lat, $lng, $radio): bool {
                return Geolocalizacion::distanciaKm(
                    $lat,
                    $lng,
                    (float) $oferta->latitud,
                    (float) $oferta->longitud,
                ) <= $radio;
            })->values();
        }

        return response()->json([
            'data' => OfertaResource::collection($ofertas),
        ]);
    }

    public function store(OfertaRequest $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user->esCiudadano() && $user->estaAprobado() && $user->ciudadano, 403,
            'Solo los ciudadanos aprobados pueden publicar ofertas.');

        $validated = $request->validated();

        $resultado = (new OfertaService())->publicar($user->ciudadano, $validated, $request->file('foto'));

        if ($resultado->mal()) {
            throw ValidationException::withMessages([
                'categoria_id' => $resultado->mensaje(),
            ]);
        }

        $oferta = $resultado->valor()->load(['ciudadano.usuario', 'categoria']);

        return response()->json([
            'message' => 'Oferta publicada correctamente.',
            'data' => new OfertaResource($oferta),
        ], 201);
    }

    public function show(Oferta $oferta): JsonResponse
    {
        $oferta->load(['ciudadano.usuario', 'categoria']);

        return response()->json([
            'data' => new OfertaResource($oferta),
        ]);
    }

    public function cancelar(Oferta $oferta, Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user->esCiudadano() && $user->ciudadano, 403,
            'No tiene permisos para cancelar esta oferta.');

        $resultado = (new OfertaService())->cancelar($user->ciudadano, $oferta);

        if ($resultado->mal()) {
            return response()->json(['message' => $resultado->mensaje()],
                $resultado->codigo() === 'sin_permiso' ? 403 : 422);
        }

        return response()->json([
            'message' => 'Oferta cancelada correctamente.',
            'data' => new OfertaResource($resultado->valor()->load(['ciudadano.usuario', 'categoria'])),
        ]);
    }

    public function mias(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user->esCiudadano() && $user->ciudadano, 403,
            'Solo los ciudadanos pueden consultar sus ofertas.');

        $ofertas = Oferta::query()
            ->with(['ciudadano.usuario', 'categoria'])
            ->where('ciudadano_id', $user->ciudadano->id)
            ->latest('fecha_publicacion')
            ->get();

        return response()->json([
            'data' => OfertaResource::collection($ofertas),
        ]);
    }
}