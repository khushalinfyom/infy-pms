<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class CustomEditProfile extends EditProfile
{
    public static function getLabel(): string
    {
        return __('messages.settings.profile_settings');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make()
                    ->schema([

                        Group::make([

                            SpatieMediaLibraryFileUpload::make('image_path')
                                ->label(__('messages.common.profile'))
                                ->collection(User::IMAGE_PATH)
                                ->image()
                                ->imageEditor(),

                        ]),

                        Group::make([

                            TextInput::make('name')
                                ->label(__('messages.common.name'))
                                ->placeholder(__('messages.common.name'))
                                ->required(),

                            TextInput::make('email')
                                ->label(__('messages.common.email'))
                                ->placeholder(__('messages.common.email'))
                                ->email()
                                ->unique()
                                ->columnSpanFull()
                                ->required(),

                            PhoneInput::make('phone')
                                ->defaultCountry('IN')
                                ->separateDialCode(true)
                                ->countryStatePath('region_code')
                                ->label(__('messages.common.phone'))
                                ->rules(function (Get $get) {
                                    return [
                                        'phone:AUTO,' . strtoupper($get('prefix_code')),
                                    ];
                                })
                                ->validationMessages([
                                    'phone' => __('messages.settings.phone_number_validation'),
                                ]),

                        ])->columnSpan(3)->columns(1)
                    ])
                    ->columnSpanFull()
                    ->columns(4),
            ])
            ->inlineLabel(false);
    }

    protected function getRedirectUrl(): ?string
    {
        return route('filament.admin.pages.dashboard');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.settings.profile_updated_successfully');
    }
}
