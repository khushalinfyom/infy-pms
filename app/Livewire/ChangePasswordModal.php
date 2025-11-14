<?php

namespace App\Livewire;

use App\Models\User;
use Closure;
use Exception;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ChangePasswordModal extends Component implements HasForms
{
    use InteractsWithForms;

    protected $listeners = ['resetFormData'];

    public ?array $data = [];

    public function render()
    {
        return view('livewire.change-password-modal');
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                TextInput::make('current_password')
                    ->label('Current Password' . ':')
                    ->validationAttribute('Current Password')
                    ->placeholder('Current Password')
                    ->password()
                    ->required()
                    ->revealable()
                    ->rule(static function (Get $get): Closure {
                        return static function ($attribute, $value, Closure $fail) use ($get) {
                            /** @var \App\Models\User */
                            $user = Auth::user();
                            if (! password_verify($get('current_password'), $user->password)) {
                                $fail('Current password does not match.');
                            }
                        };
                    }),

                TextInput::make('new_password')
                    ->label('New Password' . ':')
                    ->validationAttribute('New Password')
                    ->placeholder('New Password')
                    ->password()
                    ->required()
                    ->revealable()
                    ->maxLength(255)
                    ->rules(['min:8']),

                TextInput::make('new_password_confirmation')
                    ->label('Confirm Password' . ':')
                    ->validationAttribute('Confirm Password')
                    ->placeholder('Confirm Password')
                    ->password()
                    ->required()
                    ->revealable()
                    ->same('new_password')
                    ->maxLength(255),
            ])
            ->statePath('data');
    }

    public function save()
    {
        $this->form->validate();
        try {
            /** @var User $user */
            $user = Auth::user();
            $user->password = bcrypt($this->data['new_password']);
            $user->save();

            $this->form->fill();
            $this->dispatch('close-modal', id: 'change-password-modal');

            Notification::make()
                ->success()
                ->title('Password changed successfully.')
                ->send();
        } catch (Exception $exception) {
            Notification::make()
                ->danger()
                ->title($exception->getMessage())
                ->send();
        }
    }
}
