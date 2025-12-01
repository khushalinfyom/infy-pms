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
use Filament\Notifications\Notification;
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
                    ->label(__('messages.projects.new_task'))
                    ->modalWidth('2xl')
                    ->icon('heroicon-s-plus')
                    ->modalHeading(__('messages.projects.create_task'))
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
                    ->successNotificationTitle(__('messages.projects.task_created_successfully')),

                Action::make('new time entry')
                    ->label(__('messages.projects.new_time_entry'))
                    ->icon('heroicon-s-clock')
                    ->modalWidth('2xl')
                    ->modalHeading(__('messages.projects.create_time_entry'))
                    ->form($this->createTimeEntryForm())
                    ->after(function (array $data) {
                        if (!isset($data['duration']) || empty($data['duration'])) {
                            $start = Carbon::parse($data['start_time']);
                            $end = Carbon::parse($data['end_time']);
                            $seconds = $start->diffInSeconds($end);
                            $minutes = round($seconds / 60, 2);
                            $data['duration'] = $minutes;
                        }

                        return TimeEntry::create($data);
                    })
                    ->visible(authUserHasPermission('manage_time_entries'))
                    ->successNotificationTitle(__('messages.projects.time_entry_created_successfully')),

                Action::make('copyTodayActivity')
                    ->label(__('messages.projects.copy_today_activity'))
                    ->icon('heroicon-s-document-duplicate')
                    ->action(function () {
                        $timeEntries = TimeEntry::getTodayEntries();

                        $note = '** Today Time entry Activity - ' . now()->format('jS M Y') . "**\n";

                        $projects = [];

                        foreach ($timeEntries as $entry) {
                            $projectName = $entry->task->project->name;
                            $taskId = $entry->task_id;

                            $projects[$projectName][$taskId]['name'] = $entry->task->title;

                            if (!isset($projects[$projectName][$taskId]['note'])) {
                                $projects[$projectName][$taskId]['note'] = '';
                            }

                            $projects[$projectName][$taskId]['note'] .= "\n" . $entry->note . "\n";
                        }

                        foreach ($projects as $name => $project) {
                            $note .= "\n" . $name . "\n";

                            foreach ($project as $task) {
                                $note .= "\n* " . $task['name'];
                                $note .= $task['note'];
                            }
                        }

                        Notification::make()
                            ->title(__('messages.projects.today_activity_copied_successfully'))
                            ->send();
                    })

            ])
                ->label('Actions')
                ->button(),
        ];
    }

    public function createTimeEntryForm()
    {
        return [

            Hidden::make('entry_type')
                ->default(TimeEntry::VIA_FORM),

            Hidden::make('user_id')
                ->default(auth()->user()->id),

            Select::make('user_id')
                ->label(__('messages.users.user'))
                ->relationship('createdUser', 'name')
                ->required()
                ->native(false)
                ->default(auth()->user()->id)
                ->disabled()
                ->columnSpanFull(),

            Group::make([
                Group::make([

                    Select::make('project_id')
                        ->label(__('messages.projects.project'))
                        ->relationship(
                            'project',
                            'name',
                            fn($query) =>
                            $query->whereNull('deleted_at')
                                ->where('status', 1)
                                ->whereHas('users', function ($q) {
                                    $q->where('user_id', auth()->id());
                                })
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function (callable $set, callable $get) {
                            $set('task_id', null);
                        }),

                    DateTimePicker::make('start_time')
                        ->label(__('messages.settings.start_time'))
                        ->placeholder(__('messages.settings.start_time'))
                        ->required()
                        ->native(false)
                        ->maxDate(now())
                        ->live()
                        ->afterStateUpdated(
                            fn($state, callable $set, callable $get) =>
                            $this->updateDuration($get, $set)
                        ),

                    DateTimePicker::make('end_time')
                        ->label(__('messages.settings.end_time'))
                        ->placeholder(__('messages.settings.end_time'))
                        ->required()
                        ->native(false)
                        ->maxDate(now())
                        ->live()
                        ->default(now())
                        ->afterStateUpdated(
                            fn($state, callable $set, callable $get) =>
                            $this->updateDuration($get, $set)
                        ),

                    TextInput::make('duration')
                        ->label(__('messages.settings.duration_in_minutes'))
                        ->placeholder(__('messages.settings.duration'))
                        ->disabled()
                        ->required()
                        ->live(),

                ])
                    ->columns(1),

                Group::make([

                    Select::make('task_id')
                        ->label(__('messages.projects.task'))
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
                        ->required()
                        ->native(false),

                    Select::make('activity_type_id')
                        ->label(__('messages.settings.activity_type'))
                        ->relationship('timeEntries.activityType', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->native(false),

                    Textarea::make('note')
                        ->label(__('messages.settings.note'))
                        ->placeholder(__('messages.settings.note'))
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
