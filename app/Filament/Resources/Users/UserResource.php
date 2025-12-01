<?php

namespace App\Filament\Resources\Users;

use App\Enums\AdminPanelSidebar;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Filament\Tables\Columns\UserImageColumn;
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

    protected static ?int $navigationSort = AdminPanelSidebar::USERS->value;

    protected static ?string $recordTitleAttribute = 'User';

    public static function canViewAny(): bool
    {
        return authUserHasPermission('manage_users');
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.users.users');
    }

    public static function getLabel(): ?string
    {
        return __('messages.users.users');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                Hidden::make('created_by')
                    ->default(auth()->user()->id),

                TextInput::make('name')
                    ->label(__('messages.common.name'))
                    ->placeholder(__('messages.common.name'))
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

                TextInput::make('email')
                    ->label(__('messages.common.email'))
                    ->placeholder(__('messages.common.email'))
                    ->email()
                    ->unique()
                    ->columnSpanFull()
                    ->required(),

                TextInput::make('password')
                    ->label(__('messages.users.new_password'))
                    ->placeholder(__('messages.users.new_password'))
                    ->password()
                    ->minLength(8)
                    ->revealable()
                    ->visible(function (?string $operation) {
                        return $operation == 'create';
                    }),

                TextInput::make('password_confirmation')
                    ->label(__('messages.users.confirm_password'))
                    ->placeholder(__('messages.users.confirm_password'))
                    ->password()
                    ->revealable()
                    ->same('password')
                    ->visible(function (?string $operation) {
                        return $operation == 'create';
                    }),

                Select::make('projects')
                    ->label(__('messages.projects.projects'))
                    ->multiple()
                    ->relationship('projects', 'name')
                    ->preload()
                    ->searchable()
                    ->columnSpanFull(),

                Select::make('role_id')
                    ->label(__('messages.users.role'))
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
                    ->label(__('messages.users.salary'))
                    ->numeric()
                    ->placeholder(__('messages.users.salary')),

                SpatieMediaLibraryFileUpload::make('image_path')
                    ->label(__('messages.common.profile'))
                    ->collection(User::IMAGE_PATH)
                    ->image()
                    ->imageEditor(),

                Toggle::make('is_active')
                    ->label(__('messages.common.status'))
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
                        ->label(__('messages.common.profile'))
                        ->collection(User::IMAGE_PATH)
                        ->circular()
                        ->width(90)
                        ->height(90)
                        ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . $record->name),

                    Group::make([

                        TextEntry::make('name')
                            ->label(__('messages.common.name'))
                            ->hiddenLabel(),

                        TextEntry::make('email')
                            ->label(__('messages.common.email'))
                            ->hiddenLabel(),

                        TextEntry::make('phone')
                            ->label(__('messages.common.phone'))
                            ->placeholder('N/A')
                            ->inlineLabel()
                            ->formatStateUsing(fn($record) => getPhoneNumberFormate($record->phone ?? '', $record->region_code ?? null)),
                    ])
                        ->columns(1),

                ])
                    ->columns(2)
                    ->columnSpanFull(),

                TextEntry::make('status')
                    ->label(__('messages.common.status'))
                    ->getStateUsing(fn($record) => $record->is_active ? 'Active' : 'Inactive'),

                TextEntry::make('salary')
                    ->label(__('messages.users.salary'))
                    ->placeholder('N/A'),

                TextEntry::make('role_name')
                    ->label(__('messages.users.role'))
                    ->getStateUsing(fn($record) => $record->roles->first()?->name ?? 'N/A'),

                TextEntry::make('pending_task')
                    ->label(__('messages.projects.pending_tasks'))
                    ->placeholder('N/A')
                    ->default(0),

                TextEntry::make('projects')
                    ->label(__('messages.projects.projects'))
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
            ->recordActionsColumnLabel(__('messages.common.action'))
            ->emptyStateHeading(function ($livewire) {
                if (empty($livewire->tableSearch)) {
                    return __('messages.common.empty_table_heading', ['table' => 'users']);
                } else {
                    return __('messages.common.empty_table_search_heading', ['table' => 'users', 'search' => $livewire->tableSearch]);
                }
            })
            ->recordTitleAttribute('User')
            ->columns([

                UserImageColumn::make('profile')
                    ->label(__('messages.users.user'))
                    ->sortable()
                    ->searchable(['name', 'email']),

                ToggleColumn::make('is_active')
                    ->label(__('messages.users.active'))
                    ->afterStateUpdated(function () {
                        Notification::make()
                            ->title(__('messages.users.active_status_updated_successfully'))
                            ->success()
                            ->send();
                    }),

                TextColumn::make('email_verified_at')
                    ->label(__('messages.projects.projects'))
                    ->formatStateUsing(function (User $record) {
                        return $record->projects()->count();
                    })
                    ->badge()
                    ->alignCenter(),

                TextColumn::make('task')
                    ->label(__('messages.projects.task'))
                    ->default(0)
                    ->badge()
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label(__('messages.projects.created_at'))
                    ->dateTime('d M, Y h:i A'),
            ])
            ->filters([

                SelectFilter::make('status')
                    ->label(__('messages.common.status'))
                    ->options(User::STATUS)
                    ->native(false)
                    ->default(User::ACTIVE)
                    ->query(function (Builder $query, array $data): Builder {
                        $status = $data['value'];
                        if ($status == User::ACTIVE) {
                            $query->where('is_active', true)->whereNull('deleted_at');
                        } elseif ($status == User::DEACTIVE) {
                            $query->where('is_active', false)->whereNull('deleted_at');
                        } elseif ($status == User::ARCHIVED) {
                            $query->whereNotNull('deleted_at');
                        }
                        return $query;
                    }),

                // TrashedFilter::make()->label('Trashed'),

            ])
            ->deferFilters(false)
            ->recordActions([

                ViewAction::make()
                    ->tooltip(__('messages.common.view'))
                    ->iconButton()
                    ->modalWidth('md')
                    ->modalHeading(__('messages.users.view_user')),

                EditAction::make()
                    ->tooltip(__('messages.common.edit'))
                    ->iconButton()
                    ->modalWidth('md')
                    ->modalHeading(__('messages.users.edit_user'))
                    ->successNotificationTitle(__('messages.users.user_updated_successfully')),

                \App\Filament\Actions\CustomDeleteAction::make()
                    ->setCommonProperties()
                    ->iconButton()
                    ->tooltip(__('messages.common.delete'))
                    ->modalHeading(__('messages.users.delete_user'))
                    ->successNotificationTitle(__('messages.users.user_deleted_successfully')),

                ForceDeleteAction::make()
                    ->tooltip(__('messages.users.force_delete'))
                    ->iconButton()
                    ->modalHeading(__('messages.users.force_delete_user'))
                    ->successNotificationTitle(__('messages.users.user_force_deleted_successfully')),

                RestoreAction::make()
                    ->tooltip(__('messages.users.restore'))
                    ->iconButton()
                    ->modalHeading(__('messages.users.restore_user'))
                    ->successNotificationTitle(__('messages.users.user_restored_successfully')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    \App\Filament\Actions\CustomDeleteBulkAction::make()
                        ->setCommonProperties()
                        ->modalHeading(__('messages.users.delete_selected_users'))
                        ->successNotificationTitle(__('messages.users.users_deleted_successfully')),

                    ForceDeleteBulkAction::make()
                        ->modalHeading(__('messages.users.force_delete_users'))
                        ->successNotificationTitle(__('messages.users.users_force_deleted_successfully')),

                    RestoreBulkAction::make()
                        ->modalHeading(__('messages.users.restore_users'))
                        ->successNotificationTitle(__('messages.users.users_restored_successfully')),
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
