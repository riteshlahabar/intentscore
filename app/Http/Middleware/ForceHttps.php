<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirects plain HTTP to HTTPS in production, so admin credentials, session
 * cookies and demo passwords are never sent unencrypted even if a link or
 * bookmark points at http://. Left inactive outside production so local/staging
 * environments without TLS are not broken.
 */
class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production') && ! $request->secure()) {
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request);
    }
}
