<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $host = $request->getHost();
        $isLocal = in_array($host, ['127.0.0.1', 'localhost', '::1']) || str_ends_with($host, '.test') || str_ends_with($host, '.local');

        if ($request->isSecure() && !$isLocal) {
            // HSTS: Instruct browsers to only connect via HTTPS for 1 year in production
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        } elseif ($isLocal) {
            // Clear any accidental local HSTS cache in browser
            $response->headers->set('Strict-Transport-Security', 'max-age=0');
        }

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Content Security Policy tailored for e-commerce payment gateways & Google OAuth
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://*.esewa.com.np https://*.khalti.com https://*.fonepay.com https://accounts.google.com; " .
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
               "font-src 'self' https://fonts.gstatic.com; " .
               "img-src 'self' data: https:; " .
               "connect-src 'self' https://*.esewa.com.np https://*.khalti.com https://*.fonepay.com; " .
               "frame-src 'self' https://accounts.google.com https://*.esewa.com.np https://*.khalti.com https://*.fonepay.com;";

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
