<?php

namespace App\Filament\Pages;

use AbanoubNassem\FilamentGRecaptchaField\Forms\Components\GRecaptcha;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Blade;
use Filament\Notifications\Notification;
use Filament\Models\Contracts\FilamentUser;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\MultiFactor\Contracts\HasBeforeChallengeHook;
use Filament\Auth\Pages\Login;
use Filament\Schemas\Schema;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;

class CustomLogin extends Login
{
    /**
     * @var view-string
     */
    protected string $view = 'filament.pages.custom-login';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent()
                    ->label(__('messages.common.email_address'))
                    ->placeholder(__('messages.common.email_address')),

                $this->getPasswordFormComponent()
                    ->label(__('messages.users.password'))
                    ->placeholder(__('messages.users.password'))
                    ->hint(filament()->hasPasswordReset() ? new HtmlString(Blade::render('<x-filament::link :href="filament()->getRequestPasswordResetUrl()" tabindex="3"> {{ "Forgot password?" }}</x-filament::link>')) : null)
                    ->extraAttributes(['class' => 'password-field']),

                $this->getRememberFormComponent()
                    ->label(__('messages.users.remember_me')),

                // GRecaptcha::make('captcha')
                //     ->columnSpanFull()
                //     ->hiddenLabel()
                //     ->visible(function () {
                //         return enableCaptcha() ?? false;
                //     }),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            $this->getAuthenticateFormAction()
                ->extraAttributes(['class' => 'w-full flex items-center justify-center space-x-3 form-submit'])
                ->label(__('messages.users.login')),
        ];
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        /** @var SessionGuard $authGuard */
        $authGuard = Filament::auth();

        $authProvider = $authGuard->getProvider();
        /** @phpstan-ignore-line */
        $credentials = $this->getCredentialsFromFormData($data);

        $user = $authProvider->retrieveByCredentials($credentials);

        if ((! $user) || (! $authProvider->validateCredentials($user, $credentials))) {
            $this->userUndertakingMultiFactorAuthentication = null;

            $this->fireFailedEvent($authGuard, $user, $credentials);
            $this->throwFailureValidationException();
        }

        if (isset($data['email']) && !empty($data['email'])) {
            $user = User::whereEmail($data['email'])->first();
            if ($user) {
                if ($user->email_verified_at == null) {
                    Notification::make()
                        ->title(__('messages.common.email_not_verified'))
                        ->danger()
                        ->send();

                    return null;
                }
                if ($user->is_active == 0) {
                    Notification::make()
                        ->title(__('messages.users.account_not_activated'))
                        ->danger()
                        ->send();

                    return null;
                }
            }
        }

        if (
            filled($this->userUndertakingMultiFactorAuthentication) &&
            (decrypt($this->userUndertakingMultiFactorAuthentication) === $user->getAuthIdentifier())
        ) {
            $this->multiFactorChallengeForm->validate();
        } else {
            foreach (Filament::getMultiFactorAuthenticationProviders() as $multiFactorAuthenticationProvider) {
                if (! $multiFactorAuthenticationProvider->isEnabled($user)) {
                    continue;
                }

                $this->userUndertakingMultiFactorAuthentication = encrypt($user->getAuthIdentifier());

                if ($multiFactorAuthenticationProvider instanceof HasBeforeChallengeHook) {
                    $multiFactorAuthenticationProvider->beforeChallenge($user);
                }

                break;
            }

            if (filled($this->userUndertakingMultiFactorAuthentication)) {
                $this->multiFactorChallengeForm->fill();

                return null;
            }
        }

        if (! $authGuard->attemptWhen($credentials, function (Authenticatable $user): bool {
            if (! ($user instanceof FilamentUser)) {
                return true;
            }

            return $user->canAccessPanel(Filament::getCurrentOrDefaultPanel());
        }, $data['remember'] ?? false)) {
            $this->fireFailedEvent($authGuard, $user, $credentials);
            $this->throwFailureValidationException();
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }
}
