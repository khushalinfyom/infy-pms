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
        return __('messages.settings.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.settings.activity_types');
    }

    public static function getLabel(): string
    {
        return __('messages.settings.activity_types');
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
                    ->label(__('messages.common.name'))
                    ->placeholder(__('messages.common.name'))
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
            ->recordActionsColumnLabel(__('messages.common.action'))
            ->emptyStateHeading(function ($livewire) {
                if (empty($livewire->tableSearch)) {
                    return __('messages.common.empty_table_heading', ['table' => 'Activity Types']);
                } else {
                    return __('messages.common.empty_table_search_heading', ['table' => 'Activity Types', 'search' => $livewire->tableSearch]);
                }
            })
            ->recordTitleAttribute('ActivityType')
            ->columns([
                TextColumn::make('name')
                    ->label(__('messages.common.name'))
                    ->placeholder(__('messages.common.name'))
                    ->sortable()
                    ->searchable(),
            ])
            ->recordActions([

                EditAction::make()
                    ->tooltip(__('messages.common.edit'))
                    ->iconButton()
                    ->modalWidth('md')
                    ->modalHeading(__('messages.settings.edit_activity_type'))
                    ->successNotificationTitle(__('messages.settings.activity_type_updated_successfully')),

                \App\Filament\Actions\CustomDeleteAction::make()
                    ->setCommonProperties()
                    ->iconButton()
                    ->tooltip(__('messages.common.delete'))
                    ->modalHeading(__('messages.settings.delete_activity_type'))
                    ->successNotificationTitle(__('messages.settings.activity_type_deleted_successfully')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    \App\Filament\Actions\CustomDeleteBulkAction::make()
                        ->setCommonProperties()
                        ->modalHeading(__('messages.settings.delete_activity_type'))
                        ->successNotificationTitle(__('messages.settings.activity_types_deleted_successfully')),
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
