<?php

namespace App\Http\Responses;

use App\Enums\Role as RoleEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     */
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        $isLiveMonitoringUser = $request->user()?->hasRole(RoleEnum::Live_Monitoring->value) === true;

        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false], 200)
            : ($isLiveMonitoringUser
                ? redirect()->intended(route('attendance-display', absolute: false))
                : redirect()->route('dashboard'));
    }
}
