<?php

namespace App\Filament\Resources\Taxes;

use App\Filament\Resources\Taxes\Pages\ManageTaxes;
use App\Models\Tax;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TaxResource extends Resource
{
    protected static ?string $model = Tax::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPercentBadge;

    public static function getNavigationGroup(): ?string
    {
        return __('messages.settings.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.settings.taxes');
    }

    public static function getLabel(): ?string
    {
        return __('messages.settings.taxes');
    }

    public static function canViewAny(): bool
    {
        return authUserHasPermission('manage_taxes');
    }

    protected static ?string $recordTitleAttribute = 'Tax';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('name')
                    ->label(__('messages.common.name'))
                    ->placeholder(__('messages.common.name'))
                    ->required(),

                TextInput::make('tax')
                    ->label(__('messages.settings.tax'))
                    ->placeholder(__('messages.settings.tax'))
                    ->numeric()
                    ->required(),
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
                    return __('messages.common.empty_table_heading', ['table' => 'Taxes']);
                } else {
                    return __('messages.common.empty_table_search_heading', ['table' => 'Taxes', 'search' => $livewire->tableSearch]);
                }
            })
            ->recordTitleAttribute('Tax')
            ->columns([

                TextColumn::make('name')
                    ->label(__('messages.common.name'))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('tax')
                    ->label(__('messages.settings.tax'))
                    ->sortable()
                    ->formatStateUsing(fn($state) => $state . ' %'),
            ])
            ->recordActions([

                EditAction::make()
                    ->tooltip(__('messages.common.edit'))
                    ->iconButton()
                    ->modalWidth('md')
                    ->modalHeading(__('messages.settings.edit_tax'))
                    ->successNotificationTitle(__('messages.settings.tax_updated_successfully')),

                \App\Filament\Actions\CustomDeleteAction::make()
                    ->setCommonProperties()
                    ->iconButton()
                    ->tooltip(__('messages.common.delete'))
                    ->modalHeading(__('messages.settings.delete_tax'))
                    ->successNotificationTitle(__('messages.settings.tax_deleted_successfully')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    \App\Filament\Actions\CustomDeleteBulkAction::make()
                        ->setCommonProperties()
                        ->modalHeading(__('messages.settings.delete_taxes'))
                        ->successNotificationTitle(__('messages.settings.taxes_deleted_successfully')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTaxes::route('/'),
        ];
    }
}
