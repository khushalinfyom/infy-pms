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

    public static function getNavigationLabel(): string
    {
        return 'Archived Users';
    }

    public static function getModelLabel(): string
    {
        return 'Archived Users';
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
            ->recordActionsColumnLabel('Action')
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
            ])
            ->recordActions([
                ForceDeleteAction::make()
                    ->label('Delete Permanently')
                    ->color('danger')
                    ->iconButton()
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->modalHeading('Permanently delete user')
                    ->modalDescription('This action cannot be undone. The user record will be permanently deleted.')
                    ->successNotificationTitle('User permanently deleted!'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ForceDeleteBulkAction::make()
                        ->label('Delete Permanently')
                        ->color('danger')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->successNotificationTitle('Users permanently deleted!'),
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
