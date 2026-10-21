<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Browser-side protections on every page, set once here so they hold whatever
 * serves the app (nginx also sets the first three for static files).
 *
 *  - no MIME sniffing, so an uploaded file is never "reinterpreted" as a script
 *  - the app cannot be framed by another site (clickjacking)
 *  - links leak only the origin, never the full URL
 *  - camera / microphone / location / payment are off, the app uses none
 *  - HSTS in production: browsers stay on HTTPS
 *  - CSP limited to the directives that never break the page: framing, <base>,
 *    form targets and plugins. (A script-src policy needs the inline Alpine
 *    handlers rewritten first; tracked separately.)
 */
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $headers->set('Content-Security-Policy', "frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'");

        if ($request->isSecure() && app()->isProduction()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
