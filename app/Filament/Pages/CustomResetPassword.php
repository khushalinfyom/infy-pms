<?php

namespace App\Filament\Pages;

use Filament\Auth\Pages\PasswordReset\ResetPassword;
use Filament\Schemas\Schema;

class CustomResetPassword extends ResetPassword
{
    protected string $view = 'filament.pages.custom-reset-password';

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
                    ->extraAttributes(['class' => 'password-field']),

                $this->getPasswordConfirmationFormComponent()
                    ->label(__('messages.users.confirm_password'))
                    ->placeholder(__('messages.users.confirm_password'))
                    ->extraAttributes(['class' => 'password-field']),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            $this->getResetPasswordFormAction()
                ->label(__('messages.users.reset_password'))
                ->extraAttributes(['class' => 'w-full flex items-center justify-center space-x-3 form-submit']),
        ];
    }
}
