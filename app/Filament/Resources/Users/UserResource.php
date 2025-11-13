<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\Role;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $recordTitleAttribute = 'User';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                Hidden::make('created_by')
                    ->default(auth()->user()->id),

                TextInput::make('name')
                    ->label('Name')
                    ->placeholder('Name')
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

                TextInput::make('email')
                    ->label('Email')
                    ->placeholder('Email')
                    ->email()
                    ->unique()
                    ->columnSpanFull()
                    ->required(),

                TextInput::make('password')
                    ->label('New Password')
                    ->placeholder('New Password')
                    ->password()
                    ->minLength(8)
                    ->revealable()
                    ->visible(function (?string $operation) {
                        return $operation == 'create';
                    }),

                TextInput::make('password_confirmation')
                    ->label('Confirm Password')
                    ->placeholder('Confirm Password')
                    ->password()
                    ->revealable()
                    ->same('password')
                    ->visible(function (?string $operation) {
                        return $operation == 'create';
                    }),

                Select::make('projects')
                    ->label('Projects')
                    ->multiple()
                    ->relationship('projects', 'name')
                    ->preload()
                    ->searchable()
                    ->columnSpanFull(),

                Select::make('role_id')
                    ->label('Role')
                    ->options(Role::pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->afterStateHydrated(function ($set, $record) {
                        if ($record) {
                            $set('role_id', $record->roles()->pluck('id')->first());
                        }
                    }),

                TextInput::make('salary')
                    ->label('Salary')
                    ->numeric()
                    ->placeholder('Salary'),

                SpatieMediaLibraryFileUpload::make('image_path')
                    ->label('Profile')
                    ->collection(User::IMAGE_PATH)
                    ->image()
                    ->imageEditor(),

                Toggle::make('is_active')
                    ->label('Status')
                    ->inline(false)
                    ->default(true),

            ])
            ->columns(2);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([

                Group::make([

                    SpatieMediaLibraryImageEntry::make('image_path')
                        ->label('Profile')
                        ->collection(User::IMAGE_PATH)
                        ->circular()
                        ->width(90)
                        ->height(90)
                        ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . $record->name),

                    Group::make([

                        TextEntry::make('name')
                            ->label('Name')
                            ->hiddenLabel(),

                        TextEntry::make('email')
                            ->label('Email')
                            ->hiddenLabel(),

                        TextEntry::make('phone')
                            ->placeholder('N/A')
                            ->inlineLabel()
                            ->formatStateUsing(fn($record) => getPhoneNumberFormate($record->phone ?? '', $record->region_code ?? null)),
                    ])
                        ->columns(1),

                ])
                    ->columns(2)
                    ->columnSpanFull(),

                TextEntry::make('status')
                    ->label('Status')
                    ->getStateUsing(fn($record) => $record->is_active ? 'Active' : 'Inactive'),

                TextEntry::make('salary')
                    ->label('Salary')
                    ->placeholder('N/A'),

                TextEntry::make('role_name')
                    ->label('Role')
                    ->getStateUsing(fn($record) => $record->roles->first()?->name ?? 'N/A'),

                TextEntry::make('pending_task')
                    ->label('Pending Tasks')
                    ->placeholder('N/A')
                    ->default(0),

                TextEntry::make('projects')
                    ->label('Projects')
                    ->html()
                    ->getStateUsing(fn($record) => $record->projects->pluck('name')->implode('<br>'))
                    ->placeholder('N/A')
                    ->columns(2)
                    ->columnSpanFull(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordAction(null)
            ->paginated([10, 25, 50, 100])
            ->defaultSort('id', 'desc')
            ->recordActionsColumnLabel('Actions')
            ->emptyStateHeading(function ($livewire) {
                if (empty($livewire->tableSearch)) {
                    return 'No users found.';
                } else {
                    return 'No users found for "' . $livewire->tableSearch . '".';
                }
            })
            ->recordTitleAttribute('User')
            ->columns([

                SpatieMediaLibraryImageColumn::make('image_path')
                    ->collection(User::IMAGE_PATH)
                    ->label('Profile')
                    ->circular()
                    ->width(40)
                    ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . $record->name),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->description(function (User $record) {
                        return $record->email;
                    }),

                ToggleColumn::make('is_active')
                    ->label('Is Active')
                    ->afterStateUpdated(function () {
                        Notification::make()
                            ->title('User active status updated successfully!')
                            ->success()
                            ->send();
                    }),

                TextColumn::make('created_at')
                    ->label('Projects')
                    ->formatStateUsing(function (User $record) {
                        return $record->projects()->count();
                    })
                    ->badge(),

                TextColumn::make('task')
                    ->label('Task Active')
                    ->default(0)
                    ->badge(),
            ])
            ->filters([

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(User::STATUS)
                    ->native(false)
                    ->query(function (Builder $query, array $data): Builder {
                        $status = $data['value'];
                        if ($status == User::ACTIVE) {
                            $query->where('is_active', true)->whereNull('deleted_at');
                        } elseif ($status == User::DEACTIVE) {
                            $query->where('is_active', false)->whereNull('deleted_at');
                        }
                        elseif ($status == User::ARCHIVED) {
                            $query->whereNotNull('deleted_at');
                            // dd($query->get());
                        }
                        return $query;
                    }),

                // TrashedFilter::make()->label('Trashed'),

            ])
            ->deferFilters(false)
            ->recordActions([

                ViewAction::make()
                    ->tooltip('View')
                    ->iconButton()
                    ->modalWidth('md')
                    ->modalHeading('View User'),

                EditAction::make()
                    ->tooltip('Edit')
                    ->iconButton()
                    ->modalWidth('md')
                    ->modalHeading('Edit User')
                    ->successNotificationTitle('User updated successfully!'),

                \App\Filament\Actions\CustomDeleteAction::make()
                    ->setCommonProperties()
                    ->iconButton()
                    ->tooltip('Delete')
                    ->modalHeading('Delete User')
                    ->successNotificationTitle('User deleted successfully!'),

                ForceDeleteAction::make()
                    ->tooltip('Force Delete')
                    ->iconButton()
                    ->modalHeading('Force Delete User')
                    ->successNotificationTitle('User force deleted successfully!'),

                RestoreAction::make()
                    ->tooltip('Restore')
                    ->iconButton()
                    ->modalHeading('Restore User')
                    ->successNotificationTitle('User restored successfully!'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    \App\Filament\Actions\CustomDeleteBulkAction::make()
                        ->setCommonProperties()
                        ->modalHeading('Delete Users')
                        ->successNotificationTitle('Users deleted successfully!'),

                    ForceDeleteBulkAction::make()
                        ->modalHeading('Force Delete Users')
                        ->successNotificationTitle('Users force deleted successfully!'),

                    RestoreBulkAction::make()
                        ->modalHeading('Restore Users')
                        ->successNotificationTitle('Users restored successfully!'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
