<?php

namespace App\Filament\Resources\Clients;

use App\Filament\Resources\Clients\Pages\ManageClients;
use App\Filament\Resources\Departments\DepartmentResource;
use App\Models\Client;
use App\Models\Department;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Hash;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $recordTitleAttribute = 'Client';

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

                Select::make('department_id')
                    ->label('Department')
                    ->options(Department::all()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->suffixAction(function (Set $set, Get $get) {
                        return DepartmentResource::getSuffixAction($set, 'department_id');
                    }),

                TextInput::make('email')
                    ->label('Email')
                    ->placeholder('Email')
                    ->email()
                    ->unique()
                    ->columnSpanFull()
                    ->required(),

                ...self::getPasswordFields(),

                TextInput::make('website')
                    ->label('Website')
                    ->placeholder('Website')
                    ->url()
                    ->columnSpanFull(),

                SpatieMediaLibraryFileUpload::make('clients')
                    ->label('Profile')
                    ->disk(config('app.media_disk'))
                    ->collection('clients')
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Name'),

                TextEntry::make('department.name')
                    ->label('Department'),

                TextEntry::make('email')
                    ->label('Email address'),

                TextEntry::make('website')
                    ->label('Website')
                    ->placeholder('N/A'),
            ])
            ->columns(2);
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
                    return 'No clients found.';
                } else {
                    return 'No clients found for "' . $livewire->tableSearch . '".';
                }
            })
            ->recordTitleAttribute('Client')
            ->columns([

                SpatieMediaLibraryImageColumn::make('clients')
                    ->label('Profile')
                    ->disk(config('app.media_disk'))
                    ->collection('clients')
                    ->circular()
                    ->width(40)
                    ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . $record->name),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable(['name', 'email'])
                    ->description(function (Client $record) {
                        return $record->email;
                    })
                    ->limit(100)
                    ->wrap(),

                TextColumn::make('department.name')
                    ->label('Department')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('department_id')
                    ->label('Department')
                    ->options(Department::all()->pluck('name', 'id'))
                    ->native(false)
                    ->searchable()
                    ->preload(),
            ])
            ->deferFilters(false)
            ->recordActions([
                ViewAction::make()
                    ->iconButton()
                    ->modalHeading('View Client')
                    ->modalWidth('xl')
                    ->tooltip('View'),

                EditAction::make()
                    ->iconButton()
                    ->modalHeading('Edit Client')
                    ->modalWidth('xl')
                    ->tooltip('Edit')
                    ->successNotificationTitle('Client updated successfully!')
                    ->mutateFormDataUsing(function (array $data, $record): array {
                        $user = $record->user;

                        if ($user && !empty($data['password'])) {
                            $user->update([
                                'password' => Hash::make($data['password']),
                            ]);
                        }

                        if (!$user && !empty($data['active']) && $data['active'] === true) {
                            if (User::where('email', $data['email'])->exists()) {
                                Notification::make()
                                    ->title('Email already exists! Use another email.')
                                    ->danger()
                                    ->send();

                                throw new Halt();
                            }

                            $newUser = User::create([
                                'name' => $data['name'],
                                'email' => $data['email'],
                                'password' => Hash::make($data['password']),
                                'created_by' => auth()->id(),
                            ]);

                            $newUser->assignRole('Client');
                            $data['user_id'] = $newUser->id;
                        }

                        return $data;
                    }),


                \App\Filament\Actions\CustomDeleteAction::make()
                    ->setCommonProperties()
                    ->iconButton()
                    ->tooltip('Delete')
                    ->modalHeading('Delete Client')
                    ->successNotificationTitle('Client deleted successfully!'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    \App\Filament\Actions\CustomDeleteBulkAction::make()
                        ->setCommonProperties()
                        ->modalHeading('Delete Clients')
                        ->successNotificationTitle('Clients deleted successfully!'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageClients::route('/'),
        ];
    }

    public static function getPasswordFields(): array
    {
        return [
            Checkbox::make('active')
                ->label('Want to create client panel ?')
                ->columnSpanFull()
                ->live()
                ->visible(
                    fn(string $operation, Get $get) =>
                    $operation === 'create' || ($operation === 'edit' && !$get('user_id'))
                ),

            Checkbox::make('change_password')
                ->label('Want to change password ?')
                ->columnSpanFull()
                ->live()
                ->visible(
                    fn(string $operation, Get $get) =>
                    $operation === 'edit' && $get('user_id')
                ),

            TextInput::make('password')
                ->label(function (string $operation, Get $get) {
                    if ($operation === 'edit' && $get('user_id')) {
                        return 'New Password';
                    }
                    return 'Password';
                })
                ->placeholder(function (string $operation, Get $get) {
                    if ($operation === 'edit' && $get('user_id')) {
                        return 'Enter New Password';
                    }
                    return 'Enter Password';
                })
                ->password()
                ->minLength(8)
                ->required(function (string $operation, Get $get) {
                    if ($operation === 'create' && $get('active')) {
                        return true;
                    }

                    if ($operation === 'edit' && !$get('user_id') && $get('active')) {
                        return true;
                    }

                    if ($operation === 'edit' && $get('user_id') && $get('change_password')) {
                        return true;
                    }

                    return false;
                })
                ->revealable()
                ->live()
                ->visible(function (string $operation, Get $get) {
                    if ($operation === 'create' && $get('active')) {
                        return true;
                    }

                    if ($operation === 'edit' && !$get('user_id') && $get('active')) {
                        return true;
                    }

                    if ($operation === 'edit' && $get('user_id') && $get('change_password')) {
                        return true;
                    }

                    return false;
                }),

            TextInput::make('confirm_password')
                ->label('Confirm Password')
                ->placeholder('Confirm Password')
                ->password()
                ->same('password')
                ->required(function (string $operation, Get $get) {
                    if ($operation === 'create' && $get('active')) {
                        return true;
                    }

                    if ($operation === 'edit' && !$get('user_id') && $get('active')) {
                        return true;
                    }

                    if ($operation === 'edit' && $get('user_id') && $get('change_password')) {
                        return true;
                    }

                    return false;
                })
                ->revealable()
                ->visible(function (string $operation, Get $get) {
                    if ($operation === 'create' && $get('active')) {
                        return true;
                    }

                    if ($operation === 'edit' && !$get('user_id') && $get('active')) {
                        return true;
                    }

                    if ($operation === 'edit' && $get('user_id') && $get('change_password')) {
                        return true;
                    }

                    return false;
                })
                ->maxLength(255),
        ];
    }
}
