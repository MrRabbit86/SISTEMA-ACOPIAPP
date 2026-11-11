<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    public function recientes(Request $request): JsonResponse
    {
        $user = $request->user();

        $items = $user->notifications()
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'titulo' => $n->data['titulo'] ?? 'Notificación',
                'mensaje' => $n->data['mensaje'] ?? '',
                'url' => $n->data['url'] ?? route('dashboard'),
                'tipo' => $n->data['tipo'] ?? 'info',
                'leida' => $n->read_at !== null,
                'fecha' => $n->created_at?->diffForHumans(),
            ]);

        return response()->json([
            'no_leidas' => $user->unreadNotifications()->count(),
            'items' => $items,
        ]);
    }

    public function leer(string $id, Request $request): RedirectResponse|JsonResponse
    {
        $notificacion = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notificacion->markAsRead();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect($notificacion->data['url'] ?? route('dashboard'));
    }

    public function leerTodas(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back();
    }
}
