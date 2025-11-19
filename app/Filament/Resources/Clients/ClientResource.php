<?php

namespace App\Filament\Resources\Clients;

use App\Enums\AdminPanelSidebar;
use App\Filament\Infolists\Components\ClientEntry;
use App\Filament\Resources\Clients\Pages\ManageClients;
use App\Filament\Resources\Departments\DepartmentResource;
use App\Models\Client;
use App\Models\Department;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = AdminPanelSidebar::CLIENTS->value;

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

                Section::make('')
                    ->schema([
                        Group::make([

                            ClientEntry::make('client')
                                ->client(fn($record) => $record)
                                ->columnSpanFull()
                                ->hiddenLabel(),
                        ])
                            ->columns(3),

                        Group::make([

                            TextEntry::make('department.name')
                                ->label('Department')
                                ->badge()
                                ->color('info'),

                            TextEntry::make('website')
                                ->label('Website')
                                ->placeholder('N/A')
                                ->url(fn(Client $record) => filled($record->website) ? $record->website : null)
                                ->openUrlInNewTab()
                                ->extraAttributes(fn(Client $record) => [
                                    'style' => filled($record->website)
                                        ? '
                                        color:#0d6efd;
                                        text-decoration:underline;
                                        cursor:pointer;
                                        display:inline-block;
                                        max-width:200px;
                                        white-space:nowrap;
                                        overflow:hidden;
                                        text-overflow:ellipsis;
                                      '
                                        : '
                                        color:#6c757d;
                                        display:inline-block;
                                        max-width:200px;
                                        white-space:nowrap;
                                        overflow:hidden;
                                        text-overflow:ellipsis;
                                      ',
                                ]),
                        ])
                            ->columns(2),

                    ])
                    ->columnSpanFull()
                    ->columns(1),

                Group::make([

                    Section::make('')
                        ->schema([
                            TextEntry::make('created_at')
                                ->label('Total Projects')
                                ->formatStateUsing(fn(Client $record) => $record->projects->count())
                                ->extraAttributes([
                                    'style' => '
                                                color:#2315ff;
                                                font-size:18px;
                                                font-weight:600;
                                            ',
                                ])
                        ])
                        ->columnSpanFull()
                        ->extraAttributes([
                            'class' => 'total-projects',
                        ]),

                    Section::make('')
                        ->schema([
                            TextEntry::make('project_progress.completedProjects')
                                ->label('Finished Projects')
                                ->extraAttributes([
                                    'style' => 'color:#226c14;
                                                font-size:18px;
                                                font-weight:600;',
                                ]),
                        ])
                        ->extraAttributes([
                            'class' => 'finished-projects'
                        ]),

                    Section::make('')
                        ->schema([
                            TextEntry::make('project_progress.openProjects')
                                ->label('Open Projects')
                                ->extraAttributes([
                                    'style' => 'color:#3b1d74;
                                                font-size:18px;
                                                font-weight:600;',
                                ]),
                        ])
                        ->extraAttributes([
                            'class' => 'open-projects'
                        ]),

                    Section::make('')
                        ->schema([
                            TextEntry::make('project_progress.holdProjects')
                                ->label('Hold Projects')
                                ->extraAttributes([
                                    'style' => 'color:#734120;
                                                font-size:18px;
                                                font-weight:600;',
                                ]),
                        ])
                        ->extraAttributes([
                            'class' => 'hold-projects'
                        ]),

                    Section::make('')
                        ->schema([
                            TextEntry::make('project_progress.archivedProjects')
                                ->label('Archived Projects')
                                ->extraAttributes([
                                    'style' => 'color:#222223;
                                                font-size:18px;
                                                font-weight:600;',
                                ]),
                        ])
                        ->extraAttributes([
                            'class' => 'archived-projects'
                        ]),

                ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->extraAttributes([
                        'class' => 'client',
                    ]),

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
                    ->searchable()
                    ->placeholder('N/A'),
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
                    })
                    ->after(function ($record) {
                        activity()
                            ->causedBy(getLoggedInUser())
                            ->performedOn($record)
                            ->withProperties([
                                'model' => Client::class,
                                'data'  => '',
                            ])
                            ->useLog('Client Updated')
                            ->log('Client updated');
                    }),

                \App\Filament\Actions\CustomDeleteAction::make()
                    ->setCommonProperties()
                    ->iconButton()
                    ->tooltip('Delete')
                    ->modalHeading('Delete Client')
                    ->before(function ($record) {
                        $record->update([
                            'deleted_by' => auth()->id(),
                        ]);
                    })
                    ->successNotificationTitle('Client deleted successfully!'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    \App\Filament\Actions\CustomDeleteBulkAction::make()
                        ->setCommonProperties()
                        ->modalHeading('Delete Clients')
                        ->before(function ($records) {
                            foreach ($records as $record) {
                                $record->update([
                                    'deleted_by' => auth()->id(),
                                ]);
                            }
                        })
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

    public static function getSuffixAction($inputName = null, $departmentInputName = null, $departmentId = null, $recordId = null)
    {
        $record = null;
        if (!empty($recordId)) {
            $record = Client::find($recordId);
        }
        return Action::make('createClient')
            ->icon(function () use ($record) {
                if (isset($record) && $record) {
                    return 'heroicon-s-pencil-square';
                } else {
                    return 'heroicon-s-plus';
                }
            })
            ->modalWidth('md')
            ->label(function () use ($record) {
                if (isset($record) && $record) {
                    return 'Edit Client';
                } else {
                    return 'New Client';
                }
            })
            ->modalHeading(function () use ($record) {
                if (isset($record) && $record) {
                    return 'Edit Client';
                } else {
                    return 'Create Client';
                }
            })
            ->form([
                TextInput::make('name')
                    ->label('Name')
                    ->placeholder('Name')
                    ->required(),

                TextInput::make('email')
                    ->label('Email')
                    ->placeholder('Email')
                    ->email()
                    ->unique(Client::class, 'email')
                    ->columnSpanFull()
                    ->required(),

                Select::make('department_id')
                    ->label('Department')
                    ->options(Department::all()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
            ])
            ->action(function (array $data, Set $set) use ($inputName, $departmentInputName, $record) {
                if (isset($record) && $record) {
                    $record->update([
                        'department_id' => $data['department_id'],
                        'name' => $data['name'],
                        'email' => $data['email'],
                    ]);
                    Notification::make()
                        ->title('Client updated successfully!')
                        ->success()
                        ->send();
                } else {
                    $record = Client::create([
                        'department_id' => $data['department_id'],
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'created_by' => Auth::user()->id,
                    ]);

                    activity()
                        ->causedBy(getLoggedInUser())
                        ->performedOn($record)
                        ->withProperties([
                            'model' => Client::class,
                            'data'  => '',
                        ])
                        ->useLog('New Client Created')
                        ->log('New Client ' . $record->name . ' created');

                    Notification::make()
                        ->title('Client created successfully!')
                        ->success()
                        ->send();
                }
                $set($departmentInputName, $data['department_id']);
                $set($inputName, $record->id);
            });
    }
}
