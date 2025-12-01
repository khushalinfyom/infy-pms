<?php

namespace App\Filament\Resources\Invoices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordAction(null)
            ->paginated([10, 25, 50, 100])
            ->defaultSort('id', 'desc')
            ->recordActionsColumnLabel(__('messages.common.action'))
            ->emptyStateHeading(function ($livewire) {
                if (empty($livewire->tableSearch)) {
                    return __('messages.common.empty_table_heading', ['table' => 'invoices']);
                } else {
                    return __('messages.common.empty_table_search_heading', ['table' => 'invoices', 'search' => $livewire->tableSearch]);
                }
            })
            ->columns([

                TextColumn::make('invoice_number')
                    ->label('Invoice Number')
                    ->formatStateUsing(function ($state, $record) {
                        return 'INV-' . $record->invoice_number;
                    })
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),

                TextColumn::make('invoiceProjects.name')
                    ->label('Project')
                    ->wrap()
                    ->placeholder('N/A'),

                TextColumn::make('amount')
                    ->label('Amount'),

                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->placeholder('N/A'),
            ])
            ->filters([
                // TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->tooltip(__('messages.common.view'))
                    ->iconButton()
                    ->modalHeading(__('messages.users.view_department'))
                    ->modalWidth('md'),

                EditAction::make()
                    ->tooltip(__('messages.common.edit'))
                    ->iconButton()
                    ->modalHeading(__('messages.users.edit_department'))
                    ->modalWidth('xl')
                    ->successNotificationTitle(__('messages.users.department_updated_successfully')),

                \App\Filament\Actions\CustomDeleteAction::make()
                    ->setCommonProperties()
                    ->iconButton()
                    ->tooltip(__('messages.common.delete'))
                    ->modalHeading(__('messages.users.delete_department'))
                    ->successNotificationTitle(__('messages.users.department_deleted_successfully')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    \App\Filament\Actions\CustomDeleteBulkAction::make()
                        ->setCommonProperties()
                        ->modalHeading(__('messages.users.delete_selected_departments'))
                        ->successNotificationTitle(__('messages.users.departments_deleted_successfully')),
                ]),
            ]);
    }
}
