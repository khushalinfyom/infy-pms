<?php

namespace App\Filament\Resources\Calenders\Widgets;

use App\Models\Task;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
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
            ->form($this->getFormSchema())
            ->mutateFormDataUsing(function (array $data, $record) {

                if (!isset($data['duration']) || empty($data['duration'])) {
                    $start = Carbon::parse($data['start_time']);
                    $end = Carbon::parse($data['end_time']);
                    $seconds = $start->diffInSeconds($end);
                    $minutes = round($seconds / 60, 2);

                    $data['duration'] = $minutes;
                }

                return $data;
            })
            ->successNotificationTitle('Time Entry updated successfully!');
    }

    public function getFormSchema(): array
    {
        return [

            Hidden::make('entry_type')
                ->default(TimeEntry::VIA_FORM),

            Hidden::make('task_id')
                ->default($this->record->id),

            Hidden::make('user_id')
                ->default(auth()->user()->id),

            Select::make('user_id')
                ->label('User')
                ->relationship('user', 'name')
                ->required()
                ->native(false)
                ->default(auth()->user()->id)
                ->disabled()
                ->columnSpanFull(),

            Group::make([
                Group::make([

                    Select::make('project_id')
                        ->label('Project')
                        ->options([
                            $this->record->task->project_id => $this->record->task->project->name,
                        ])
                        ->default($this->record->task->project_id)
                        ->required()
                        ->native(false)
                        ->disabled()
                        ->afterStateHydrated(function (callable $set, callable $get) {
                            $set('project_id', $this->record->task->project_id);
                        })
                        ->live()
                        ->afterStateUpdated(function (callable $set, callable $get) {
                            $set('task_id', null);
                        }),

                    DateTimePicker::make('start_time')
                        ->label('Start Time')
                        ->placeholder('Start Time')
                        ->required()
                        ->native(false)
                        ->maxDate(now())
                        ->live()
                        ->afterStateUpdated(
                            fn($state, callable $set, callable $get) =>
                            self::updateDuration($get, $set)
                        ),

                    DateTimePicker::make('end_time')
                        ->label('End Time')
                        ->placeholder('End Time')
                        ->required()
                        ->native(false)
                        ->maxDate(now())
                        ->minDate($this->record->start_time)
                        ->live()
                        ->default(now())
                        ->afterStateUpdated(
                            fn($state, callable $set, callable $get) =>
                            self::updateDuration($get, $set)
                        ),

                    TextInput::make('duration')
                        ->label('Duration (In Minutes)')
                        ->placeholder('Duration')
                        ->disabled()
                        ->required()
                        ->live(),

                ])
                    ->columns(1),

                Group::make([

                    Select::make('task_id')
                        ->label('Task')
                        ->required()
                        ->native(false)
                        ->options(function (callable $get) {
                            $projectId = $get('project_id');

                            if (!$projectId) {
                                return [];
                            }

                            return Task::where('project_id', $projectId)
                                ->whereNull('deleted_at')
                                ->where('status', '!=', 1)
                                ->pluck('title', 'id')
                                ->toArray();
                        })
                        ->searchable()
                        ->preload()
                        ->default($this->record->id),

                    Select::make('activity_type_id')
                        ->label('Activity Type')
                        ->relationship('activityType', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->native(false),

                    Textarea::make('note')
                        ->label('Note')
                        ->placeholder('Note')
                        ->required()
                        ->maxLength(255),

                ])
                    ->columns(1),

            ])
                ->columns(2),
        ];
    }

    public function fetchEvents(array $fetchInfo): array
    {
        return TimeEntry::query()
            ->where('user_id', auth()->id())
            ->get()
            ->map(function ($event) {

                $startTime = $event->start_time ? Carbon::parse($event->start_time)->format('g:i A') : '';
                $endTime   = $event->end_time ? Carbon::parse($event->end_time)->format('g:i A') : '';

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

    protected function updateDuration(callable $get, callable $set)
    {
        $start = $get('start_time');
        $end = $get('end_time');

        if ($start && $end) {
            $startTime = Carbon::parse($start);
            $endTime = Carbon::parse($end);

            $seconds = $startTime->diffInSeconds($endTime);
            $minutes = round($seconds / 60, 2);

            $set('duration', $minutes);
        } else {
            $set('duration', 0);
        }
    }
}
