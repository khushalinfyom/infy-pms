<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\UserNotification;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Group;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ManageTasks extends ManageRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([

                CreateAction::make()
                    ->label('New Task')
                    ->modalWidth('2xl')
                    ->icon('heroicon-s-plus')
                    ->modalHeading('Create Task')
                    ->createAnother(false)
                    ->using(function (array $data) {

                        $selectedType = $data['estimate_time_type'] ?? null;

                        if ($selectedType === null) {
                            $data['estimate_time_type'] = Task::IN_HOURS;
                        } else {
                            $data['estimate_time_type'] = $selectedType;
                        }

                        if (empty($data['estimate_time'])) {
                            $data['estimate_time'] = null;
                        } else {
                            if ($data['estimate_time_type'] == Task::IN_HOURS) {
                                if (is_string($data['estimate_time'])) {
                                } else {
                                    $data['estimate_time'] = '00:00';
                                }
                            } else {
                                $data['estimate_time'] = is_numeric($data['estimate_time']) ? $data['estimate_time'] : 0;
                            }
                        }

                        if (empty($data['task_number']) && isset($data['project_id'])) {
                            $data['task_number'] = Task::generateUniqueTaskNumber($data['project_id']);
                        }

                        $data['created_by'] = Auth::id();

                        return Task::create($data);
                    })
                    ->after(function ($record, $data) {
                        if (! empty($data['taskAssignee'])) {
                            foreach ($data['taskAssignee'] as $userId) {
                                DB::table('task_assignees')->insert([
                                    'task_id' => $record->id,
                                    'user_id' => $userId,
                                ]);
                            }
                        }

                        $userIds = $data['users'] ?? $record->taskAssignee()->pluck('users.id')->toArray();

                        foreach ($userIds as $id) {
                            UserNotification::create([
                                'title'       => 'New Task Assigned',
                                'description' => $record->title . ' assigned to you',
                                'type'        => Task::class,
                                'user_id'     => $id,
                            ]);
                        }

                        $project = $record->project;

                        if ($project) {
                            activity()
                                ->causedBy(getLoggedInUser())
                                ->performedOn($project)
                                ->withProperties([
                                    'model' => Task::class,
                                    'data'  => 'of ' . $project->name,
                                ])
                                ->useLog('Task Created')
                                ->log('Created new task ' . $record->title);
                        }
                    })
                    ->successNotificationTitle('Task created successfully!'),

                Action::make('new time entry')
                    ->label('New Time Entry')
                    ->icon('heroicon-s-clock')
                    ->modalWidth('2xl')
                    ->modalHeading('Create Time Entry')
                    ->form($this->createTimeEntryForm())
                    // ->using(function (array $data) {
                    //     if (!isset($data['duration']) || empty($data['duration'])) {
                    //         $start = Carbon::parse($data['start_time']);
                    //         $end = Carbon::parse($data['end_time']);
                    //         $seconds = $start->diffInSeconds($end);
                    //         $minutes = round($seconds / 60, 2);
                    //         $data['duration'] = $minutes;
                    //     }

                    //     return TimeEntry::create($data);
                    // })
                    ->successNotificationTitle('Time Entry created successfully!'),

                Action::make('copy today activity')
                    ->label('Copy Today Activity')
                    ->icon('heroicon-s-document-duplicate')
                    ->successNotificationTitle('Today Activity copied successfully!'),
            ])
                ->label('Actions')
                ->button(),
        ];
    }

    public function createTimeEntryForm()
    {
        return [

            // Hidden::make('entry_type')
            //     ->default(TimeEntry::VIA_FORM),

            // Hidden::make('task_id')
            //     ->default($this->record->id),

            // Hidden::make('user_id')
            //     ->default(auth()->user()->id),

            Select::make('user_id')
                ->label('User')
                ->relationship('user', 'name')
                ->required()
                ->native(false)
                ->columnSpanFull(),

            Group::make([
                Group::make([

                    Select::make('project_id')
                        ->label('Project')
                        ->relationship('task.project', 'name')
                        ->required()
                        ->native(false)
                        // ->afterStateHydrated(function (callable $set, callable $get) {
                        //     $set('project_id', $this->record->project_id);
                        // })
                        ,

                    DateTimePicker::make('start_time')
                        ->label('Start Time')
                        ->placeholder('Start Time')
                        ->required()
                        ->native(false)
                        ->maxDate(now())
                        ->live()
                        ->afterStateUpdated(
                            fn($state, callable $set, callable $get) =>
                            $this->updateDuration($get, $set)
                        ),

                    DateTimePicker::make('end_time')
                        ->label('End Time')
                        ->placeholder('End Time')
                        ->required()
                        ->native(false)
                        ->maxDate(now())
                        // ->minDate($this->record->start_time)
                        ->live()
                        ->default(now())
                        ->afterStateUpdated(
                            fn($state, callable $set, callable $get) =>
                            $this->updateDuration($get, $set)
                        ),

                    TextInput::make('duration')
                        ->label('Duration')
                        ->placeholder('Duration')
                        ->disabled()
                        ->required()
                        ->live(),

                ])
                    ->columns(1),

                Group::make([

                    Select::make('task_id')
                        ->label('Task')
                        ->relationship('task', 'title')
                        ->required()
                        ->native(false)
                        // ->default($this->record->title)
                        ->disabled(),

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
