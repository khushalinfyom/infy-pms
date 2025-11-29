<?php

namespace App\Filament\Resources\Statuses;

use App\Filament\Resources\Statuses\Pages\ManageStatuses;
use App\Models\Status;
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
use Illuminate\Contracts\Support\Htmlable;

class StatusResource extends Resource
{
    protected static ?string $model = Status::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedViewColumns;

    protected static ?string $recordTitleAttribute = 'Status';

    public static function getNavigationGroup(): ?string
    {
        return __('messages.settings.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.settings.task_status');
    }

    public static function getPluralLabel(): ?string
    {
        return __('messages.settings.task_status');
    }

    public static function canViewAny(): bool
    {
        return authUserHasPermission('manage_status');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                Hidden::make('status')
                    ->default(function () {
                        $lastStatus = Status::max('status');
                        return $lastStatus ? $lastStatus + 1 : 1;
                    }),

                TextInput::make('name')
                    ->label(__('messages.common.name'))
                    ->placeholder(__('messages.common.name'))
                    ->required()
                    ->unique()
                    ->maxLength(170),

                TextInput::make('order')
                    ->label(__('messages.settings.order'))
                    ->numeric()
                    ->required()
                    ->default(function () {
                        $lastOrder = Status::max('order');
                        return $lastOrder ? $lastOrder + 1 : 1;
                    }),

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
                    return __('messages.common.empty_table_heading', ['table' => 'Statuses']);
                } else {
                    return __('messages.common.empty_table_search_heading', ['table' => 'Statuses', 'search' => $livewire->tableSearch]);
                }
            })
            ->recordTitleAttribute('Status')
            ->columns([
                TextColumn::make('name')
                    ->label(__('messages.common.name'))
                    ->searchable()
                    ->sortable(),

            ])
            ->recordActions([

                EditAction::make()
                    ->tooltip(__('messages.common.edit'))
                    ->iconButton()
                    ->modalWidth('md')
                    ->modalHeading(__('messages.settings.edit_status'))
                    ->successNotificationTitle(__('messages.settings.status_updated_successfully')),

                \App\Filament\Actions\CustomDeleteAction::make()
                    ->setCommonProperties()
                    ->iconButton()
                    ->tooltip(__('messages.common.delete'))
                    ->modalHeading(__('messages.settings.delete_status'))
                    ->successNotificationTitle(__('messages.settings.status_deleted_successfully')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([

                    \App\Filament\Actions\CustomDeleteBulkAction::make()
                        ->setCommonProperties()
                        ->modalHeading(__('messages.settings.delete_statuses'))
                        ->successNotificationTitle(__('messages.settings.statuses_deleted_successfully')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStatuses::route('/'),
        ];
    }
}
