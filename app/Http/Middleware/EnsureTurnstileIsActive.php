<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTurnstileIsActive
{
    /**
     * Verify that the authenticated turnstile device is active.
     *
     * This is an extra safety layer beyond the controller check,
     * allowing the middleware to short-circuit early for disabled devices.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $turnstile = $request->user();

        if ($turnstile && ! $turnstile->status) {
            return response()->json([
                'data' => null,
                'meta' => ['timestamp' => now()->toIso8601String()],
                'errors' => [['code' => 'USER_INACTIVE', 'message' => 'This user has been deactivated.']],
            ], 403);
        }

        return $next($request);
    }
}
