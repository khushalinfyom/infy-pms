<?php

namespace App\Filament\Client\Resources\Invoices\Schemas;

use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Models\Tax;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([

                        TextInput::make('name')
                            ->label(__('messages.common.name'))
                            ->placeholder(__('messages.common.name'))
                            ->required(),

                        Select::make('client_id')
                            ->label('Client')
                            ->multiple()
                            ->disabled()
                            ->options(fn($livewire) => $livewire->clientsOptions)
                            ->required(),

                        Select::make('project_id')
                            ->label('Project')
                            ->multiple()
                            ->disabled()
                            ->options(fn($livewire) => $livewire->projectsOptions)
                            ->required(),

                        Select::make('tax_id')
                            ->label('Tax')
                            ->options(Tax::all()->pluck('name', 'id')->toArray())
                            ->live()
                            ->native(false),

                        DatePicker::make('issue_date')
                            ->label('Issue Date')
                            ->default(now())
                            ->placeholder('Issue Date')
                            ->maxDate(now())
                            ->live()
                            ->native(false)
                            ->required(),

                        DatePicker::make('due_date')
                            ->label('Due Date')
                            ->placeholder('Due Date')
                            ->native(false)
                            ->minDate(now()->subDays(1))
                            ->live(),

                        TextInput::make('invoice_number')
                            ->label('Invoice Number')
                            ->prefix('INV-')
                            ->disabled()
                            ->required(),

                        Select::make('discount_type')
                            ->label('Discount Type')
                            ->native(false)
                            ->live()
                            ->options([
                                '0' => 'No Discount',
                                '1' => 'Before Tax',
                                '2' => 'After Tax',
                            ])
                            ->required(),

                        TextInput::make('discount')
                            ->label('Discount (%)')
                            ->placeholder('0')
                            ->live()
                            ->default(0)
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->placeholder('Notes')
                            ->columnSpanFull(),

                        Repeater::make('invoice_items')
                            ->label('Task Details')
                            ->itemLabel(fn(?array $state): string => $state['item_name'] ?? 'Task')
                            ->columns(3)
                            ->schema([
                                TextInput::make('item_name')
                                    ->label('Task')
                                    ->disabled()
                                    ->columnSpan(1),

                                TextInput::make('hours')
                                    ->label('Hours')
                                    ->disabled()
                                    ->numeric()
                                    ->columnSpan(1),

                                TextInput::make('amount')
                                    ->label('Amount')
                                    ->disabled()
                                    ->numeric()
                                    ->columnSpan(1),
                            ])
                            ->default(fn(EditInvoice $livewire) => $livewire->invoiceItems)
                            ->disableItemCreation()   // prevent adding new tasks from edit form
                            ->disableItemDeletion()
                            ->columnSpanFull(),


                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }
}
