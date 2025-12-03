<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Models\Invoice;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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
                    ->formatStateUsing(function ($record) {
                        return 'INV-' . $record->invoice_number;
                    })
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),

                TextColumn::make('invoiceProjects.name')
                    ->label('Project')
                    ->wrap()
                    ->placeholder('N/A')
                    ->searchable(),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->prefix('$ ')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(function ($record) {
                        return Invoice::STATUS[$record->status];
                    })
                    ->badge()
                    ->colors([
                        'warning' => Invoice::STATUS_DRAFT,
                        'info' => Invoice::STATUS_SENT,
                        'success' => Invoice::STATUS_PAID,
                    ]),

                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->placeholder('N/A'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        '' => 'ALL',
                        Invoice::STATUS_SENT => 'SENT',
                        Invoice::STATUS_DRAFT => 'DRAFT',
                        Invoice::STATUS_PAID => 'PAID',
                    ])
                    ->default(Invoice::STATUS_SENT)
                    ->native(false),
            ])
            ->deferFilters(false)
            ->recordActions([
                ViewAction::make()
                    ->tooltip(__('messages.common.view'))
                    ->iconButton()
                    ->modalHeading(__('messages.users.view_invoice'))
                    ->modalWidth('md'),

                EditAction::make()
                    ->tooltip(__('messages.common.edit'))
                    ->iconButton()
                    ->modalHeading(__('messages.users.edit_invoice'))
                    ->modalWidth('xl')
                    ->hidden(fn($record) => $record->status === Invoice::STATUS_PAID)
                    ->successNotificationTitle(__('messages.users.invoice_updated_successfully')),

                \App\Filament\Actions\CustomDeleteAction::make()
                    ->setCommonProperties()
                    ->iconButton()
                    ->tooltip(__('messages.common.delete'))
                    ->modalHeading(__('messages.users.delete_invoice'))
                    ->successNotificationTitle(__('messages.users.invoice_deleted_successfully')),
            ]);
    }
}
