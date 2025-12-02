<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected string $view = 'filament.resources.invoices.pages.view-invoice';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('mark_as_paid')
                ->label(__('messages.users.mark_as_paid'))
                ->icon('heroicon-s-check-circle')
                ->color('success')
                ->visible(fn() => $this->record->status !== \App\Models\Invoice::STATUS_PAID)
                ->action(fn() => $this->markAsPaid())
                ->successNotificationTitle('Invoice Paid Successfully.'),

            Action::make('print_invoice')
                ->label(__('messages.users.print_invoice'))
                ->icon('heroicon-s-printer')
                ->color('info')
                ->url(fn() => url('invoices/' . $this->record->id . '/pdf'), shouldOpenInNewTab: true),

            Action::make('back')
                ->label(__('messages.common.back'))
                ->icon('heroicon-s-arrow-left')
                ->url(InvoiceResource::getUrl('index'))
                ->color('gray'),
        ];
    }

    public function getTitle(): string
    {
        return __('messages.users.view_invoice');
    }

    public function markAsPaid(): void
    {
        $this->record->update(['status' => \App\Models\Invoice::STATUS_PAID]);
    }
}
