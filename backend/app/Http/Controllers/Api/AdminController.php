<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UsuarioResource;
use App\Models\User;
use App\Services\UsuarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function usuarios(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        abort_unless($request->user()->esAdmin(), 403, 'Acceso restringido al administrador.');

        $query = User::query()
            ->with(['ciudadano', 'empresa'])
            ->latest();

        if ($request->filled('estado')) {
            $query->where('estado', $request->string('estado'));
        }

        if ($request->filled('rol')) {
            $query->where('rol', $request->string('rol'));
        }

        return UsuarioResource::collection($query->paginate($request->integer('per_page', 15)));
    }

    public function aprobar(User $user, Request $request): JsonResponse
    {
        abort_unless($request->user()->esAdmin(), 403, 'Acceso restringido al administrador.');

        $resultado = (new UsuarioService())->aprobar($user);

        if ($resultado->mal()) {
            return response()->json(['message' => $resultado->mensaje()], 422);
        }

        return response()->json([
            'message' => "La cuenta de {$user->name} fue aprobada correctamente.",
            'usuario' => new UsuarioResource($user->load(['ciudadano', 'empresa'])),
        ]);
    }

    public function rechazar(User $user, Request $request): JsonResponse
    {
        abort_unless($request->user()->esAdmin(), 403, 'Acceso restringido al administrador.');

        $resultado = (new UsuarioService())->rechazar($user);

        if ($resultado->mal()) {
            return response()->json(['message' => $resultado->mensaje()], 422);
        }

        return response()->json([
            'message' => "La cuenta de {$user->name} fue rechazada.",
            'usuario' => new UsuarioResource($user->load(['ciudadano', 'empresa'])),
        ]);
    }
}