<?php

namespace App\Http\Responses;

use App\Models\Role;
use App\Models\User;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as ContractsLoginResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

class LoginResponse implements ContractsLoginResponse
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request): Redirector|RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user) {
            $role = $user->roles()->first();
            if ($role && $role->name === User::ADMIN) {
                return redirect()->route('filament.admin.pages.dashboard');
            }
            else {
                return redirect()->route('filament.client.pages.dashboard');
            }
        }

        return redirect()->route('home');
    }
}
