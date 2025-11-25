<?php

namespace App\Filament\Resources\Calenders\Widgets;

use App\Models\TimeEntry;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Saade\FilamentFullCalendar\Actions\EditAction;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarWidget extends FullCalendarWidget
{
    public Model | string | null $model = TimeEntry::class;

    public function config(): array
    {
        return [
            'firstDay' => 1,
            'initialView' => 'dayGridWeek',
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridWeek,dayGridDay',
            ],
        ];
    }

    protected function headerActions(): array
    {
        return [];
    }

    protected function viewAction(): Action
    {
        return EditAction::make()
            ->modalWidth('xl')
            ->modalHeading('Edit Time Entry')
            ->record(fn($livewire) => $livewire->getRecord())
            ->form($this->getFormSchema());
    }

    public function getFormSchema(): array
    {
        return [];
    }

    public function fetchEvents(array $fetchInfo): array
    {
        return TimeEntry::query()
            ->get()
            ->map(function ($event) {

                $startTime = $event->start_time ? \Carbon\Carbon::parse($event->start_time)->format('g:i A') : '';
                $endTime   = $event->end_time ? \Carbon\Carbon::parse($event->end_time)->format('g:i A') : '';

                $title = "{$event->task->title} ({$startTime} to {$endTime})";

                return [
                    'id'    => $event->id,
                    'title' => $title,
                    'start' => $event->start_time,
                    'end'   => $event->end_time,
                ];
            })
            ->toArray();
    }
}
