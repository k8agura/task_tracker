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

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), geolocation=(), microphone=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        if (app()->isProduction()) {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; "
                ."base-uri 'self'; "
                ."form-action 'self'; "
                ."frame-ancestors 'none'; "
                ."object-src 'none'; "
                ."img-src 'self' data: blob:; "
                ."font-src 'self' data:; "
                ."style-src 'self' 'unsafe-inline'; "
                ."script-src 'self' 'unsafe-inline' 'unsafe-eval'; "
                ."connect-src 'self' ws: wss: http: https:;"
            );
        }

        return $response;
    }
}
