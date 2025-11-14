<?php

namespace App\Filament\Resources\Roles\Tables;

use App\Models\Role;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordAction(null)
            ->paginated([10, 25, 50, 100])
            ->defaultSort('id', 'desc')
            ->recordActionsColumnLabel('Actions')
            ->emptyStateHeading(function ($livewire) {
                if (empty($livewire->tableSearch)) {
                    return 'No roles found.';
                } else {
                    return 'No roles found for "' . $livewire->tableSearch . '".';
                }
            })
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->width(200),

                TextColumn::make('permissions')
                    ->label('Permissions')
                    ->getStateUsing(fn($record) => $record->permissions->pluck('display_name'))
                    ->width(1200)
                    ->wrap()
                    ->badge(),
            ])
            ->recordActions([

                ViewAction::make()
                    ->iconButton()
                    ->tooltip('View'),

                EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit')
                    ->hidden(fn(Role $record): bool => $record->name === 'Admin'),

                \App\Filament\Actions\CustomDeleteAction::make()
                    ->setCommonProperties()
                    ->iconButton()
                    ->tooltip('Delete')
                    ->modalHeading('Delete Role')
                    ->successNotificationTitle('Role deleted successfully!')
                    ->hidden(fn(Role $record): bool => $record->name === 'Admin'),
            ]);
    }
}
