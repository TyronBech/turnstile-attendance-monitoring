<?php

namespace App\Http\Responses;

use App\Enums\Role as RoleEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;

class LoginResponse implements LoginResponseContract
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
            ? new JsonResponse(['two_factor' => false], 200)
            : redirect()->intended($redirectPath);
    }
}
