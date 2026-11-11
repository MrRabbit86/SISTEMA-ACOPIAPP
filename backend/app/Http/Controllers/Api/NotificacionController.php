<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $items = $user->notifications()
            ->latest()
            ->limit(25)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'titulo' => $n->data['titulo'] ?? 'Notificación',
                'mensaje' => $n->data['mensaje'] ?? '',
                'url' => $n->data['url'] ?? null,
                'tipo' => $n->data['tipo'] ?? 'info',
                'leida' => $n->read_at !== null,
                'fecha' => $n->created_at?->toISOString(),
            ]);

        return response()->json([
            'no_leidas' => $user->unreadNotifications()->count(),
            'data' => $items,
        ]);
    }

    public function leerTodas(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['message' => 'Notificaciones marcadas como leídas.']);
    }
}
