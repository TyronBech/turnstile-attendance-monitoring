<?php

namespace App\Http\Middleware;

use App\Enums\Role as RoleEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictLiveMonitoringAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->hasRole(RoleEnum::Live_Monitoring->value)) {
            abort(403);
        }

        return $next($request);
    }
}
