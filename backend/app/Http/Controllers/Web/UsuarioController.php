<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UsuarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()
            ->with(['ciudadano', 'empresa'])
            ->latest();

        if ($request->filled('estado')) {
            $query->where('estado', $request->query('estado'));
        }

        if ($request->filled('rol')) {
            $query->where('rol', $request->query('rol'));
        }

        return view('admin.usuarios', [
            'usuarios' => $query->paginate(15),
            'filtro_estado' => $request->query('estado', ''),
            'filtro_rol' => $request->query('rol', ''),
        ]);
    }

    public function aprobar(User $user): RedirectResponse
    {
        $resultado = (new UsuarioService())->aprobar($user);

        abort_if($resultado->mal(), 422, $resultado->mensaje());

        return back()->with('status', "La cuenta de {$user->name} fue aprobada correctamente.");
    }

    public function rechazar(User $user): RedirectResponse
    {
        $resultado = (new UsuarioService())->rechazar($user);

        abort_if($resultado->mal(), 422, $resultado->mensaje());

        return back()->with('status', "La cuenta de {$user->name} fue rechazada.");
    }
}