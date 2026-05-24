<?php

namespace App\Http\Responses;

use App\Enums\Role as RoleEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Fortify;

class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     */
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        $redirectPath = $request->user()?->hasRole(RoleEnum::Live_Monitoring->value)
            ? route('attendance-display', absolute: false)
            : Fortify::redirects('login');

        return $request->wantsJson()
            ? new JsonResponse('', 204)
            : redirect()->intended($redirectPath);
    }
}
