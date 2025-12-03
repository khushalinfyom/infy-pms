<?php

namespace App\Filament\Resources\ArchivedUsers;

use App\Enums\AdminPanelSidebar;
use App\Filament\Resources\ArchivedUsers\Pages\ManageArchivedUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ArchivedUserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserMinus;

    protected static ?int $navigationSort = AdminPanelSidebar::ARCHIVED_USERS->value;

    protected static ?string $recordTitleAttribute = 'User';

    public static function canViewAny(): bool
    {
        return authUserHasPermission('archived_users');
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.users.archived_users');
    }

    public static function getModelLabel(): string
    {
        return __('messages.users.archived_users');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->onlyTrashed()
            )
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

                SpatieMediaLibraryImageColumn::make('image_path')
                    ->collection(User::IMAGE_PATH)
                    ->label(__('messages.common.profile'))
                    ->circular()
                    ->width(40)
                    ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . $record->name),

                TextColumn::make('name')
                    ->label(__('messages.common.name'))
                    ->searchable()
                    ->description(function (User $record) {
                        return $record->email;
                    }),
            ])
            ->recordActions([
                ForceDeleteAction::make()
                    ->tooltip(__('messages.users.delete_permanently'))
                    ->color('danger')
                    ->iconButton()
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->modalHeading(__('messages.users.permanently_delete_user'))
                    ->successNotificationTitle(__('messages.users.user_permanently_deleted_successfully')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ForceDeleteBulkAction::make()
                        ->tooltip(__('messages.users.delete_permanently'))
                        ->color('danger')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading(__('messages.users.permanently_delete_users'))
                        ->successNotificationTitle(__('messages.users.users_permanently_deleted_successfully')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageArchivedUsers::route('/'),
        ];
    }
}
