<?php

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LogoutResponse as ContractsLogoutResponse;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;


class LogoutResponse implements ContractsLogoutResponse
{
    public function toResponse($request): Redirector|RedirectResponse
    {
        return redirect()->route('filament.admin.auth.login');
    }
}
