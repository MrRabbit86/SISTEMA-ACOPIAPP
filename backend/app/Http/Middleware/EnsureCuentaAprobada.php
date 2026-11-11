<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCuentaAprobada
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(
            $user && $user->estaAprobado(),
            403,
            'Su cuenta no está activa para operar en el sistema.',
        );

        return $next($request);
    }
}
