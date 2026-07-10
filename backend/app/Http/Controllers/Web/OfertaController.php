<?php

namespace App\Http\Controllers\Web;

use App\Enums\EstadoOferta;
use App\Http\Controllers\Controller;
use App\Models\CategoriaMaterial;
use App\Models\Oferta;
use App\Support\Geolocalizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        $oferta->load(['ciudadano.usuario', 'categoria', 'transaccion']);

        return view('ofertas.show', [
            'oferta' => $oferta,
            'geo' => [
                'lat' => (float) $oferta->latitud,
                'lng' => (float) $oferta->longitud,
                'material' => $oferta->categoria?->nombre,
            ],
        ]);
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
        $query = Oferta::query()
            ->with(['ciudadano.usuario', 'categoria'])
            ->latest('fecha_publicacion');

        if ($request->filled('estado')) {
            $query->where('estado', $request->query('estado'));
        } elseif (! $request->has('estado') || $request->query('estado') === '') {
            $query->whereIn('estado', [
                EstadoOferta::PENDIENTE->value,
                EstadoOferta::EN_PROCESO->value,
            ]);
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