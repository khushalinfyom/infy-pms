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
            ->recordUrl(null)
            ->paginated([10, 25, 50, 100])
            ->defaultSort('id', 'desc')
            ->recordActionsColumnLabel(__('messages.common.action'))
            ->emptyStateHeading(function ($livewire) {
                if (empty($livewire->tableSearch)) {
                    return __('messages.common.empty_table_heading', ['table' => 'roles']);
                } else {
                    return __('messages.common.empty_table_search_heading', ['table' => 'roles', 'search' => $livewire->tableSearch]);
                }
            })
            ->columns([
                TextColumn::make('name')
                    ->label(__('messages.common.name'))
                    ->searchable()
                    ->width(200),

                TextColumn::make('permissions')
                    ->label(__('messages.users.permissions'))
                    ->getStateUsing(fn($record) => $record->permissions->pluck('display_name'))
                    ->width(1200)
                    ->wrap()
                    ->badge(),
            ])
            ->recordActions([

                ViewAction::make()
                    ->iconButton()
                    ->modalHeading(__('messages.users.view_role'))
                    ->tooltip(__('messages.common.view')),

                EditAction::make()
                    ->iconButton()
                    ->tooltip(__('messages.common.edit'))
                    ->hidden(fn(Role $record): bool => $record->name === 'Admin'),

                \App\Filament\Actions\CustomDeleteAction::make()
                    ->setCommonProperties()
                    ->iconButton()
                    ->tooltip(__('messages.common.delete'))
                    ->modalHeading(__('messages.users.delete_role'))
                    ->successNotificationTitle(__('messages.users.role_deleted_successfully'))
                    ->hidden(fn(Role $record): bool => $record->name === 'Admin'),
            ]);
    }
}
