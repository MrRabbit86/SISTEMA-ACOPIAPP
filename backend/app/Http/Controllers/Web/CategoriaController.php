<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CategoriaMaterial;
use App\Support\NormalizadorDecimal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoriaController extends Controller
{
    public function index(): View
    {
        return view('admin.categorias', [
            'categorias' => CategoriaMaterial::query()->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $normalizados = NormalizadorDecimal::normalizar($request->all(), ['precio_referencia_kg']);
        if ($normalizados !== []) {
            $request->merge($normalizados);
        }

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:50', 'unique:categorias_material,nombre'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'precio_referencia_kg' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:999999'],
        ]);

        CategoriaMaterial::create($validated);

        return back()->with('status', 'Categoría creada correctamente.');
    }

    public function destroy(CategoriaMaterial $categoria): RedirectResponse
    {
        abort_if($categoria->ofertas()->exists(), 422, 'No se puede eliminar una categoría con ofertas asociadas.');

        $categoria->delete();

        return back()->with('status', 'Categoría eliminada.');
    }
}