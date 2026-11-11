<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransaccionRequest;
use App\Models\Oferta;
use App\Models\Transaccion;
use App\Services\CompraService;
use Illuminate\Http\RedirectResponse;
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

    public function store(TransaccionRequest $request, Oferta $oferta): RedirectResponse
    {
        abort_unless(auth()->user()->esEmpresa() && auth()->user()->estaAprobado(), 403);

        $validated = $request->validated();

        $resultado = (new CompraService())->registrar(auth()->user(), $oferta, $validated);

        if ($resultado->mal()) {
            return back()->withErrors(['oferta' => $resultado->mensaje()]);
        }

        return redirect()->route('transacciones.index')
            ->with('status', 'Compra registrada. La oferta fue marcada como completada.');
    }
}