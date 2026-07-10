<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\OfertaRequest;
use App\Models\CategoriaMaterial;
use App\Models\Oferta;
use App\Services\OfertaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MiOfertaController extends Controller
{
    public function index(): View
    {
        $ciudadano = auth()->user()->ciudadano;

        $ofertas = Oferta::query()
            ->with(['categoria', 'transaccion.empresa'])
            ->where('ciudadano_id', $ciudadano?->id)
            ->latest('fecha_publicacion')
            ->get();

        $categorias = CategoriaMaterial::query()
            ->orderBy('nombre')
            ->get();

        return view('ciudadano.mis-ofertas', compact('ofertas', 'categorias', 'ciudadano'));
    }

    public function store(OfertaRequest $request): RedirectResponse
    {
        $ciudadano = auth()->user()->ciudadano;
        $validated = $request->validated();

        $resultado = (new OfertaService())->publicar($ciudadano, $validated, $request->file('foto'));

        if ($resultado->mal()) {
            throw ValidationException::withMessages([
                'categoria_id' => $resultado->mensaje(),
            ]);
        }

        return back()->with('status', 'Oferta publicada correctamente. Las empresas ya pueden verla.');
    }

    public function cancelar(Oferta $oferta, Request $request): RedirectResponse
    {
        $ciudadano = auth()->user()->ciudadano;

        abort_unless($ciudadano, 403);

        $resultado = (new OfertaService())->cancelar($ciudadano, $oferta);

        if ($resultado->mal()) {
            if ($resultado->codigo() === 'sin_permiso') {
                abort(403);
            }

            return back()->withErrors(['oferta' => $resultado->mensaje()]);
        }

        return back()->with('status', 'Oferta cancelada correctamente.');
    }
}