<?php

namespace App\Filament\Resources\Events\Widgets;

use App\Models\Event;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Saade\FilamentFullCalendar\Actions\DeleteAction;
use Saade\FilamentFullCalendar\Actions\EditAction;
use Saade\FilamentFullCalendar\Actions\ViewAction;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class EventWidget extends FullCalendarWidget
{
    public Model | string | null $model = Event::class;

    public function config(): array
    {
        return [
            'firstDay' => 1,
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,dayGridWeek',
            ],
        ];
    }

    protected function headerActions(): array
    {
        return [];
    }

    protected function modalActions(): array
    {
        return [
            EditAction::make()
                ->modalWidth('xl')
                ->modalHeading('Edit Event')
                ->record(fn($livewire) => $livewire->getRecord())
                ->form($this->getFormSchema())
                ->mountUsing(function (Event $record, Schema $form) {
                    $form->fill($record->toArray());
                })
                ->successNotificationTitle('Event updated successfully!'),

            DeleteAction::make()
                ->record(fn($livewire) => $livewire->getRecord())
                ->modalHeading('Delete Event')
                ->modalSubheading('Are you sure you want to delete this event?')
                ->successNotificationTitle('Event deleted successfully!'),
        ];
    }

    protected function viewAction(): Action
    {
        return ViewAction::make()
            ->modalWidth('xl')
            ->modalHeading('Event Details')
            ->infolist([

                Group::make([

                    TextEntry::make('title')
                        ->label('Title'),

                    TextEntry::make('type')
                        ->label('Type')
                        ->formatStateUsing(function ($state) {
                            return Event::EVENTS[$state] ?? $state;
                        })
                        ->badge(),

                    TextEntry::make('start_date')
                        ->label('Start Date')
                        ->dateTime('j M, Y g:i A'),

                    TextEntry::make('end_date')
                        ->label('End Date')
                        ->dateTime('j M, Y g:i A'),

                    TextEntry::make('description')
                        ->label('Description')
                        ->placeholder('N/A')
                        ->html()
                        ->columnSpanFull(),
                ])
                    ->columns(2),
            ]);
    }

    public function getFormSchema(): array
    {
        return [
            Group::make([

                Hidden::make('added_by')
                    ->default(auth()->user()->id),

                TextInput::make('title')
                    ->label('Title')
                    ->placeholder('Title')
                    ->required()
                    ->columnSpanFull(),

                DateTimePicker::make('start_date')
                    ->label('Start Date')
                    ->placeholder('Start Date')
                    ->native(false)
                    ->required()
                    ->live()
                    ->maxDate(fn(callable $get) => $get('end_date')),

                DateTimePicker::make('end_date')
                    ->label('End Date')
                    ->placeholder('End Date')
                    ->native(false)
                    ->required()
                    ->live()
                    ->minDate(fn(callable $get) => $get('start_date')),

                Select::make('type')
                    ->label('Type')
                    ->options(Event::EVENTS)
                    ->native(false)
                    ->required(),

                RichEditor::make('description')
                    ->label('Description')
                    ->placeholder('Description')
                    ->columnSpanFull()
                    ->extraAttributes(['style' => 'height: 200px;'])
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link'],
                        ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                        ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
                        ['undo', 'redo'],
                    ]),

            ])
                ->columns(2),

        ];
    }

    public function fetchEvents(array $fetchInfo): array
    {
        return Event::query()
            ->get()
            ->map(function ($event) {

                $startTime = $event->start_date?->format('g:i A');
                $endTime   = $event->end_date?->format('g:i A');

                if ($event->type == Event::EVENT) {
                    $title = "{$event->title} ({$startTime} to {$endTime})";
                } else {
                    $title = $event->title;
                }

                return [
                    'id'    => $event->id,
                    'title' => $title,
                    'start' => $event->start_date,
                    'end'   => $event->end_date,
                ];
            })
            ->toArray();
    }
}
