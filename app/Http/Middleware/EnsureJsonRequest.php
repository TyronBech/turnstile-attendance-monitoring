<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureJsonRequest
{
    /**
     * Ensure the incoming request accepts and sends JSON.
     *
     * ESP32 devices must send `Accept: application/json` and
     * `Content-Type: application/json` headers. This middleware
     * rejects requests that don't comply, preventing HTML error
     * pages from being returned to embedded devices.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->expectsJson()) {
            return response()->json([
                'data' => null,
                'meta' => ['timestamp' => now()->toIso8601String()],
                'errors' => [['code' => 'INVALID_ACCEPT_HEADER', 'message' => 'This endpoint only accepts application/json requests. Set the Accept header to application/json.']],
            ], 406);
        }

        return $next($request);
    }
}
