<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\Setting;
use App\Models\Status;
use BackedEnum;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class General extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.clusters.settings.pages.general';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('messages.settings.general_settings');
    }

    public function getHeading(): string|Htmlable
    {
        return __('messages.settings.general_settings');
    }

    public ?array $data = [];

    public function mount(): void
    {
        $settingsKeys = [
            'app_name',
            'company_name',
            'company_address',
            'company_email',
            'company_region_code',
            'company_phone',
            'working_days_of_month',
            'working_hours_of_day',
            'default_task_status'
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
                ->label(__('messages.common.save'))
                ->submit('save')
                ->action('save'),
        ];
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->model(Setting::where('key', 'app_name')->first())
            ->schema([
                Section::make([

                    Group::make()
                        ->schema([
                            TextInput::make('app_name')
                                ->label(__('messages.settings.app_name'))
                                ->placeholder(__('messages.settings.app_name'))
                                ->required(),

                            TextInput::make('company_name')
                                ->label(__('messages.settings.company_name'))
                                ->placeholder(__('messages.settings.company_name'))
                                ->required(),

                            TextInput::make('company_email')
                                ->label(__('messages.settings.company_email'))
                                ->placeholder(__('messages.settings.company_email'))
                                ->required(),

                            PhoneInput::make('company_phone')
                                ->defaultCountry('IN')
                                ->separateDialCode(true)
                                ->countryStatePath('company_region_code')
                                ->label(__('messages.settings.company_phone'))
                                ->required()
                                ->rules(function (Get $get) {
                                    return [
                                        'required',
                                        'phone:AUTO,' . strtoupper($get('prefix_code')),
                                    ];
                                })
                                ->validationMessages([
                                    'phone' => __('messages.settings.phone_number_validation'),
                                ]),

                        ])
                        ->columns(2)
                        ->columnSpanFull(),

                    Group::make()
                        ->schema([

                            TextInput::make('working_days_of_month')
                                ->label(__('messages.settings.working_days_of_month'))
                                ->placeholder(__('messages.settings.working_days_of_month'))
                                ->numeric()
                                ->minValue(1)
                                ->required(),

                            TextInput::make('working_hours_of_day')
                                ->label(__('messages.settings.working_hours_of_day'))
                                ->placeholder(__('messages.settings.working_hours_of_day'))
                                ->numeric()
                                ->minValue(1)
                                ->required(),

                            Select::make('default_task_status')
                                ->label(__('messages.common.status'))
                                ->options(Status::all()->pluck('name', 'id'))
                                ->searchable(),
                        ])
                        ->columns(3)
                        ->columnSpanFull(),

                    TextInput::make('company_address')
                        ->label(__('messages.settings.company_address'))
                        ->placeholder(__('messages.settings.company_address'))
                        ->required()
                        ->columnSpanFull()
                        ->extraAttributes(['style' => 'min-height: 100px;']),

                    Group::make([

                        SpatieMediaLibraryFileUpload::make('app_logo')
                            ->label(__('messages.settings.app_logo'))
                            ->disk(config('app.media_disk'))
                            ->collection(Setting::APP_LOGO),

                        SpatieMediaLibraryFileUpload::make('app_favicon')
                            ->label(__('messages.settings.app_favicon'))
                            ->disk(config('app.media_disk'))
                            ->collection(Setting::APP_FAVICON),

                        SpatieMediaLibraryFileUpload::make('login_bg_image')
                            ->label(__('messages.settings.login_bg_image'))
                            ->disk(config('app.media_disk'))
                            ->collection(Setting::LOGIN_BG_IMAGE),

                    ])->columns(3)->columnSpanFull(),
                ])->columns(2)
            ])
            ->columns(1)
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        try {

            $AppSetting = Setting::where('key', 'app_name')->first();
            $data['app_logo'] = $AppSetting->getFirstMediaUrl(Setting::APP_LOGO) ?? asset('images/logo.png');
            $data['app_favicon'] = $AppSetting->getFirstMediaUrl(Setting::APP_FAVICON) ?? asset('images/logo.png');
            $data['login_bg_image'] = $AppSetting->getFirstMediaUrl(Setting::LOGIN_BG_IMAGE) ?? asset('images/login-bg.jpg');

            $paymentSettings = [
                'app_name',
                'company_name',
                'company_address',
                'company_email',
                'company_region_code',
                'company_phone',
                'working_days_of_month',
                'working_hours_of_day',
                'app_logo',
                'app_favicon',
                'login_bg_image',
                'default_task_status'
            ];

            foreach ($paymentSettings as $key) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $data[$key] ?? null]
                );
            }

            Notification::make()
                ->success()
                ->title(__('messages.settings.general_setting_updated_successfully'))
                ->send();
        } catch (Exception $exception) {
            Notification::make()
                ->danger()
                ->title($exception->getMessage())
                ->send();
        }
    }
}
