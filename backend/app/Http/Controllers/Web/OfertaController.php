<?php

namespace App\Http\Controllers\Web;

use App\Enums\EstadoOferta;
use App\Http\Controllers\Controller;
use App\Models\CategoriaMaterial;
use App\Models\Oferta;
use App\Services\OfertaService;
use App\Support\Geolocalizacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OfertaController extends Controller
{
    public const LAT_CENTRO = -17.42000000;

    public const LNG_CENTRO = -66.17000000;

    public const RADIO_DEFECTO = 15;

    public function mapa(Request $request): View
    {
        $ofertas = $this->buscarOfertas($request);
        $filtros = $this->filtros($request);

        return view('ofertas.mapa', [
            'ofertas' => $ofertas,
            'marcadores' => $ofertas->map(fn (Oferta $oferta) => [
                'id' => $oferta->id,
                'lat' => (float) $oferta->latitud,
                'lng' => (float) $oferta->longitud,
                'material' => $oferta->categoria?->nombre,
                'cantidad' => $oferta->cantidad_estimada_kg,
                'descripcion' => $oferta->descripcion,
                'modalidad' => $oferta->modalidadLabel(),
                'foto' => $oferta->foto_url ? asset('storage/'.$oferta->foto_url) : null,
                'zona' => $oferta->ciudadano?->zona,
                'estado' => $oferta->estado->value,
                'estado_label' => $oferta->estado->label(),
                'url' => route('ofertas.show', $oferta),
            ]),
            'categorias' => CategoriaMaterial::query()->orderBy('nombre')->get(),
            'filtros' => $filtros,
        ]);
    }

    public function listado(Request $request): View
    {
        $ofertas = $this->buscarOfertas($request);
        $filtros = $this->filtros($request);

        return view('ofertas.listado', [
            'ofertas' => $ofertas,
            'categorias' => CategoriaMaterial::query()->orderBy('nombre')->get(),
            'filtros' => $filtros,
        ]);
    }

    public function show(Oferta $oferta): View
    {
        Oferta::liberarReservasExpiradas();
        $oferta->refresh()->load(['ciudadano.usuario', 'categoria', 'transaccion', 'empresaReservada']);

        return view('ofertas.show', [
            'oferta' => $oferta,
            'motivosLiberacion' => OfertaService::MOTIVOS_LIBERACION,
            'geo' => [
                'lat' => (float) $oferta->latitud,
                'lng' => (float) $oferta->longitud,
                'material' => $oferta->categoria?->nombre,
            ],
        ]);
    }

    public function reservar(Oferta $oferta, Request $request): RedirectResponse
    {
        abort_unless($request->user()?->esEmpresa(), 403);

        $resultado = (new OfertaService())->reservar($request->user(), $oferta);

        if ($resultado->mal()) {
            return back()->withErrors(['estado' => $resultado->mensaje()]);
        }

        return redirect()
            ->route('ofertas.show', $oferta)
            ->with('status', 'Recojo reservado por 2 horas. Puedes ver el contacto y dirigirte al punto.');
    }

    public function liberar(Oferta $oferta, Request $request): RedirectResponse
    {
        abort_unless($request->user()?->esEmpresa(), 403);

        $validated = $request->validate([
            'motivo' => ['required', Rule::in(array_keys(OfertaService::MOTIVOS_LIBERACION))],
            'nota' => ['nullable', 'string', 'max:200'],
        ]);

        $resultado = (new OfertaService())->liberar(
            $request->user(),
            $oferta,
            $validated['motivo'],
            $validated['nota'] ?? null,
        );

        if ($resultado->mal()) {
            return back()->withErrors(['estado' => $resultado->mensaje()]);
        }

        $mensaje = $validated['motivo'] === 'material_no_apto'
            ? 'Oferta marcada como cancelada por material no apto.'
            : 'Oferta liberada nuevamente para que otras empresas puedan verla.';

        return redirect()
            ->route('ofertas.show', $oferta)
            ->with('status', $mensaje);
    }

    private function filtros(Request $request): array
    {
        return [
            'categoria_id' => $request->integer('categoria_id') ?: null,
            'estado' => $request->query('estado', ''),
            'lat' => $request->filled('lat') ? (float) $request->query('lat') : self::LAT_CENTRO,
            'lng' => $request->filled('lng') ? (float) $request->query('lng') : self::LNG_CENTRO,
            'radio' => $request->filled('radio') ? (int) $request->query('radio') : self::RADIO_DEFECTO,
        ];
    }

    private function buscarOfertas(Request $request): Collection
    {
        Oferta::liberarReservasExpiradas();

        $user = $request->user();
        $query = Oferta::query()
            ->with(['ciudadano.usuario', 'categoria', 'empresaReservada'])
            ->latest('fecha_publicacion');

        if ($request->filled('estado')) {
            $query->where('estado', $request->query('estado'));
        } elseif (! $request->has('estado') || $request->query('estado') === '') {
            if ($user?->esEmpresa()) {
                $query->where(function ($q) use ($user) {
                    $q->where('estado', EstadoOferta::PENDIENTE->value)
                        ->orWhere(function ($sub) use ($user) {
                            $sub->where('estado', EstadoOferta::EN_PROCESO->value)
                                ->where(function ($inner) use ($user) {
                                    $inner->whereNull('empresa_reservada_id')
                                        ->orWhere('empresa_reservada_id', $user->id);
                                });
                        });
                });
            } else {
                $query->whereIn('estado', [
                    EstadoOferta::PENDIENTE->value,
                    EstadoOferta::EN_PROCESO->value,
                ]);
            }
        }

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->integer('categoria_id'));
        }

        $ofertas = $query->get();

        $lat = $request->filled('lat') ? (float) $request->query('lat') : self::LAT_CENTRO;
        $lng = $request->filled('lng') ? (float) $request->query('lng') : self::LNG_CENTRO;
        $radio = $request->filled('radio')
            ? min((int) $request->query('radio'), Geolocalizacion::RADIO_MAX_KM)
            : self::RADIO_DEFECTO;

        return $ofertas->filter(function (Oferta $oferta) use ($lat, $lng, $radio): bool {
            return Geolocalizacion::distanciaKm(
                $lat,
                $lng,
                (float) $oferta->latitud,
                (float) $oferta->longitud,
            ) <= $radio;
        })->values();
    }
}