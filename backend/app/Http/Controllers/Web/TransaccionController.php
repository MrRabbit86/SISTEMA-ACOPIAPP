<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Oferta;
use App\Models\Transaccion;
use App\Services\CompraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransaccionController extends Controller
{
    public function index(): View
    {
        $query = Transaccion::query()
            ->with(['oferta.categoria', 'oferta.ciudadano.usuario', 'empresa.empresa'])
            ->latest('fecha_transaccion');

        if (auth()->user()->esEmpresa()) {
            $query->where('empresa_id', auth()->id());
        }

        return view('transacciones.index', [
            'transacciones' => $query->get(),
        ]);
    }

    public function store(Request $request, Oferta $oferta): RedirectResponse
    {
        abort_unless(auth()->user()->esEmpresa() && auth()->user()->estaAprobado(), 403);

        $validated = $request->validate([
            'peso_real_kg' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'precio_acordado_kg' => ['nullable', 'numeric', 'gt:0', 'max:999999'],
        ]);

        $resultado = (new CompraService())->registrar(auth()->user(), $oferta, $validated);

        if ($resultado->mal()) {
            return back()->withErrors(['oferta' => $resultado->mensaje()]);
        }

        return redirect()->route('transacciones.index')
            ->with('status', 'Compra registrada. La oferta fue marcada como completada.');
    }
}