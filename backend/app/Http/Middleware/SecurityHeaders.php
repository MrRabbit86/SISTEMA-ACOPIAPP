<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; "
            ."script-src 'self' 'unsafe-inline' https://unpkg.com; "
            ."style-src 'self' 'unsafe-inline' https://unpkg.com; "
            ."img-src 'self' data: blob: https://*.tile.openstreetmap.org https://unpkg.com; "
            ."connect-src 'self'; "
            ."font-src 'self' data:; "
            ."frame-ancestors 'self'; "
            ."base-uri 'self'; "
            ."form-action 'self'",
        );

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
