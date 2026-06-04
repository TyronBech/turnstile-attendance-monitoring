<?php

namespace App\Http\Responses;

use App\Enums\Role as RoleEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     */
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        $isLiveMonitoringUser = $request->user()?->hasRole(RoleEnum::Live_Monitoring->value) === true;

        return $request->wantsJson()
            ? new JsonResponse('', 204)
            : ($isLiveMonitoringUser
                ? redirect()->intended(route('attendance-display', absolute: false))
                : redirect()->route('dashboard'));
    }
}
