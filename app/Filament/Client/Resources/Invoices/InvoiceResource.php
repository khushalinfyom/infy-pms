<?php

namespace App\Filament\Client\Resources\Invoices;

use App\Enums\AdminPanelSidebar;
use App\Enums\ClientPanelSidebar;
use App\Filament\Client\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Client\Resources\Invoices\Pages\EditInvoice;
use App\Filament\Client\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Client\Resources\Invoices\Pages\ViewInvoice;
use App\Filament\Client\Resources\Invoices\Schemas\InvoiceForm;
use App\Filament\Client\Resources\Invoices\Schemas\InvoiceInfolist;
use App\Filament\Client\Resources\Invoices\Tables\InvoicesTable;
use App\Models\Invoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocument;

    protected static ?int $navigationSort = ClientPanelSidebar::INVOICE->value;

    protected static ?string $recordTitleAttribute = 'Invoice';

    public static function canViewAny(): bool
    {
        return authUserHasPermission('manage_invoices');
    }

    public static function form(Schema $schema): Schema
    {
        return InvoiceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InvoiceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InvoicesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'view' => ViewInvoice::route('/{record}'),
            'edit' => EditInvoice::route('/{record}/edit'),
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
