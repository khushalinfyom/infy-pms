<?php

namespace App\Filament\Resources\Reports\Pages;

use App\Filament\Resources\Reports\ReportResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class ViewReport extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ReportResource::class;

    protected string $view = 'filament.resources.reports.pages.view-report';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    protected function getHeaderActions(): array
    {
        return [

            Action::make('create_invoice')
                ->label('Create Invoice')
                ->icon('heroicon-s-document-text')
                ->color('success')
                ->url(ReportResource::getUrl('createInvoice', ['record' => $this->record->id])),

            Action::make('back')
                ->label('Back')
                ->icon('heroicon-s-arrow-left')
                ->url(ReportResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}
