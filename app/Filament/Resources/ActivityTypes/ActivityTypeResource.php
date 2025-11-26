<?php

namespace App\Filament\Resources\ActivityTypes;

use App\Filament\Resources\ActivityTypes\Pages\ManageActivityTypes;
use App\Models\ActivityType;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ActivityTypeResource extends Resource
{
    protected static ?string $model = ActivityType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function getNavigationGroup(): ?string
    {
        return 'Settings';
    }

    public static function canViewAny(): bool
    {
        return authUserHasPermission('manage_activities');
    }

    protected static ?string $recordTitleAttribute = 'ActivityType';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                Hidden::make('created_by')
                    ->default(auth()->user()->id),

                TextInput::make('name')
                    ->label('Name')
                    ->placeholder('Name')
                    ->required()
                    ->unique(),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordAction(null)
            ->paginated([10, 25, 50, 100])
            ->defaultSort('id', 'desc')
            ->recordActionsColumnLabel('Action')
            ->emptyStateHeading(function ($livewire) {
                if (empty($livewire->tableSearch)) {
                    return 'No activity types found.';
                } else {
                    return 'No activity types found for "' . $livewire->tableSearch . '".';
                }
            })
            ->recordTitleAttribute('ActivityType')
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),
            ])
            ->recordActions([

                EditAction::make()
                    ->tooltip('Edit')
                    ->iconButton()
                    ->modalWidth('md')
                    ->modalHeading('Edit Activity Type')
                    ->successNotificationTitle('Activity Type updated successfully!'),

                \App\Filament\Actions\CustomDeleteAction::make()
                    ->setCommonProperties()
                    ->iconButton()
                    ->tooltip('Delete')
                    ->modalHeading('Delete Activity Type')
                    ->successNotificationTitle('Activity Type deleted successfully!'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    \App\Filament\Actions\CustomDeleteBulkAction::make()
                        ->setCommonProperties()
                        ->modalHeading('Delete Activity Types')
                        ->successNotificationTitle('Activity Types deleted successfully!'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageActivityTypes::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
