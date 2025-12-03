<?php

namespace App\Filament\Client\Resources\Invoices\Pages;

use App\Filament\Client\Resources\Invoices\InvoiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;
}
