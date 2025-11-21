<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\Setting;
use BackedEnum;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class GoogleRecaptcha extends Page
{
    protected string $view = 'filament.clusters.settings.pages.google-recaptcha';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return 'Google ReCAPTCHA';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Google ReCAPTCHA';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $settingsKeys = [
            'show_recaptcha',
            'google_recaptcha_site_key',
            'google_recaptcha_secret_key',
        ];

        $settings = Setting::whereIn('key', $settingsKeys)->get()->pluck('value', 'key')->toArray();

        $this->form->fill($settings);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make($this->getFormActions())
                            ->key('form-actions'),
                    ])
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->submit('save')
                ->action('save'),
        ];
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->model(Setting::where('key', 'show_recaptcha')->first())
            ->schema([
                Section::make([

                    Toggle::make('show_recaptcha')
                        ->label('Show ReCAPTCHA')
                        ->live(),

                    TextInput::make('google_recaptcha_site_key')
                        ->label('Site Key')
                        ->placeholder('Site Key')
                        ->required()
                        ->visible(fn(Get $get) => $get('show_recaptcha') == true),

                    TextInput::make('google_recaptcha_secret_key')
                        ->label('Secret Key')
                        ->placeholder('Secret Key')
                        ->required()
                        ->visible(fn(Get $get) => $get('show_recaptcha') == true),


                ])->columns(1)
            ])
            ->columns(1)
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        try {

            $AppSetting = Setting::where('key', 'show_recaptcha')->first();

            $paymentSettings = [
                'show_recaptcha',
                'google_recaptcha_site_key',
                'google_recaptcha_secret_key',
            ];

            foreach ($paymentSettings as $key) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $data[$key] ?? null]
                );
            }

            Notification::make()
                ->success()
                ->title('Google ReCAPTCHA updated successfully.')
                ->send();
        } catch (Exception $exception) {
            Notification::make()
                ->danger()
                ->title($exception->getMessage())
                ->send();
        }
    }
}
