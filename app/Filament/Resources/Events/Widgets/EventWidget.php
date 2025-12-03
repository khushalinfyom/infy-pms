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
                ->modalHeading(__('messages.users.edit_event'))
                ->record(fn($livewire) => $livewire->getRecord())
                ->form($this->getFormSchema())
                ->mountUsing(function (Event $record, Schema $form) {
                    $form->fill($record->toArray());
                })
                ->visible(auth()->user()->hasRole('Admin'))
                ->successNotificationTitle(__('messages.users.event_updated_successfully')),

            DeleteAction::make()
                ->record(fn($livewire) => $livewire->getRecord())
                ->modalHeading(__('messages.users.delete_event'))
                ->modalSubheading(__('messages.common.are_you_sure_you_would_like_to_do_this'))
                ->successNotificationTitle(__('messages.users.event_deleted_successfully'))
                ->visible(auth()->user()->hasRole('Admin')),
        ];
    }

    protected function viewAction(): Action
    {
        return ViewAction::make()
            ->modalWidth('xl')
            ->modalHeading(__('messages.users.event_details'))
            ->infolist([

                Group::make([

                    TextEntry::make('title')
                        ->label(__('messages.projects.title')),

                    TextEntry::make('type')
                        ->label(__('messages.users.type'))
                        ->formatStateUsing(function ($state) {
                            return Event::EVENTS[$state] ?? $state;
                        })
                        ->badge(),

                    TextEntry::make('start_date')
                        ->label(__('messages.settings.start_date'))
                        ->dateTime('j M, Y g:i A'),

                    TextEntry::make('end_date')
                        ->label(__('messages.settings.end_date'))
                        ->dateTime('j M, Y g:i A'),

                    TextEntry::make('description')
                        ->label(__('messages.common.description'))
                        ->placeholder('N/A')
                        ->html()
                        ->columnSpanFull(),
                ])
                    ->columns(2),
            ])
            ->visible(auth()->user()->hasRole('Admin'));
    }

    public function getFormSchema(): array
    {
        return [
            Group::make([

                Hidden::make('added_by')
                    ->default(auth()->user()->id),

                TextInput::make('title')
                    ->label(__('messages.projects.title'))
                    ->placeholder(__('messages.projects.title'))
                    ->required()
                    ->columnSpanFull(),

                DateTimePicker::make('start_date')
                    ->label(__('messages.settings.start_date'))
                    ->placeholder(__('messages.settings.start_date'))
                    ->native(false)
                    ->required()
                    ->live()
                    ->maxDate(fn(callable $get) => $get('end_date')),

                DateTimePicker::make('end_date')
                    ->label(__('messages.settings.end_date'))
                    ->placeholder(__('messages.settings.end_date'))
                    ->native(false)
                    ->required()
                    ->live()
                    ->minDate(fn(callable $get) => $get('start_date')),

                Select::make('type')
                    ->label(____('messages.users.type'))
                    ->options(Event::EVENTS)
                    ->native(false)
                    ->required(),

                RichEditor::make('description')
                    ->label(__('messages.common.description'))
                    ->placeholder(__('messages.common.description'))
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
