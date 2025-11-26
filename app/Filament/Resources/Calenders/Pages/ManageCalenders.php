<?php

namespace App\Filament\Resources\Calenders\Pages;

use App\Filament\Resources\Calenders\CalenderResource;
use App\Filament\Resources\Calenders\Widgets\CalendarWidget;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Contracts\Support\Htmlable;

class ManageCalenders extends ManageRecords
{
    protected static string $resource = CalenderResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            CalendarWidget::class,
        ];
    }

    public function getTitle(): string | Htmlable
    {
        return 'Calender';
    }
}
