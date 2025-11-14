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
        return 'Profile Settings';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make()
                    ->schema([

                        Group::make([

                            SpatieMediaLibraryFileUpload::make('image_path')
                                ->label('Profile')
                                ->collection(User::IMAGE_PATH)
                                ->image()
                                ->imageEditor(),

                        ]),

                        Group::make([

                            TextInput::make('name')
                                ->label('Name')
                                ->placeholder('Name')
                                ->required(),

                            TextInput::make('email')
                                ->label('Email')
                                ->placeholder('Email')
                                ->email()
                                ->unique()
                                ->columnSpanFull()
                                ->required(),

                            PhoneInput::make('phone')
                                ->defaultCountry('IN')
                                ->separateDialCode(true)
                                ->countryStatePath('region_code')
                                ->label('Phone')
                                ->rules(function (Get $get) {
                                    return [
                                        'phone:AUTO,' . strtoupper($get('prefix_code')),
                                    ];
                                })
                                ->validationMessages([
                                    'phone' => 'Please enter a valid phone number.',
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
        return 'Profile Updated Successfully';
    }
}
