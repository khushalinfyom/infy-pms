<?php

namespace App\Filament\Resources\Taxes;

use App\Filament\Resources\Taxes\Pages\ManageTaxes;
use App\Models\Tax;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TaxResource extends Resource
{
    protected static ?string $model = Tax::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPercentBadge;

    public static function getNavigationGroup(): ?string
    {
        return 'Settings';
    }

    protected static ?string $recordTitleAttribute = 'Tax';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('name')
                    ->label('Name')
                    ->placeholder('Name')
                    ->required(),

                TextInput::make('tax')
                    ->label('Tax')
                    ->placeholder('Tax')
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
            ->recordActionsColumnLabel('Action')
            ->emptyStateHeading(function ($livewire) {
                if (empty($livewire->tableSearch)) {
                    return 'No taxes found.';
                } else {
                    return 'No taxes found for "' . $livewire->tableSearch . '".';
                }
            })
            ->recordTitleAttribute('Tax')
            ->columns([

                TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('tax')
                    ->label('Tax')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state . ' %'),
            ])
            ->recordActions([

                EditAction::make()
                    ->tooltip('Edit')
                    ->iconButton()
                    ->modalWidth('md')
                    ->modalHeading('Edit Tax')
                    ->successNotificationTitle('Tax updated successfully!'),

                \App\Filament\Actions\CustomDeleteAction::make()
                    ->setCommonProperties()
                    ->iconButton()
                    ->tooltip('Delete')
                    ->modalHeading('Delete Tax')
                    ->successNotificationTitle('Tax deleted successfully!'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    \App\Filament\Actions\CustomDeleteBulkAction::make()
                        ->setCommonProperties()
                        ->modalHeading('Delete Taxes')
                        ->successNotificationTitle('Taxes deleted successfully!'),
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
