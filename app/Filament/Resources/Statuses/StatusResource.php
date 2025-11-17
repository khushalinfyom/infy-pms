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
        return 'Settings';
    }

    public static function getNavigationLabel(): string
    {
        return 'Task Status';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Task Status';
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
                    ->required()
                    ->unique()
                    ->maxLength(170),

                TextInput::make('order')
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
            ->recordActionsColumnLabel('Actions')
            ->emptyStateHeading(function ($livewire) {
                if (empty($livewire->tableSearch)) {
                    return 'No Statuses found.';
                } else {
                    return 'No Statuses found for "' . $livewire->tableSearch . '".';
                }
            })
            ->recordTitleAttribute('Status')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

            ])
            ->recordActions([

                EditAction::make()
                    ->tooltip('Edit')
                    ->iconButton()
                    ->modalWidth('md')
                    ->modalHeading('Edit Status')
                    ->successNotificationTitle('Status updated successfully!'),

                \App\Filament\Actions\CustomDeleteAction::make()
                    ->setCommonProperties()
                    ->iconButton()
                    ->tooltip('Delete')
                    ->modalHeading('Delete Status')
                    ->successNotificationTitle('Status deleted successfully!'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([

                    \App\Filament\Actions\CustomDeleteBulkAction::make()
                        ->setCommonProperties()
                        ->modalHeading('Delete Statuses')
                        ->successNotificationTitle('Statuses deleted successfully!'),
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
