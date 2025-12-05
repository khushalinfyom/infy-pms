<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Status;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\UserNotification;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaskKanban extends Page implements HasActions, HasForms
{
    use InteractsWithActions, InteractsWithForms;

    protected static string $resource = TaskResource::class;

    protected string $view = 'filament.resources.tasks.pages.task-kanban';

    protected static ?string $title = '';

    public ?array $tasks = [];

    public function getTitle(): string
    {
        return 'Task';
    }

    // Property to hold the target status for new tasks
    public ?string $targetStatus = null;

    // Properties to hold filter values
    public ?int $project_id = null;
    public ?array $users = [];
    public ?int $user_id = null;

    public function mount(): void
    {
        // Set default project to first available project
        $firstProject = Project::with('users')->first();
        if ($firstProject) {
            $this->project_id = $firstProject->id;
            $this->users = $firstProject?->users->pluck('name', 'id')->toArray() ?? [];
        }
        $this->refreshTasks();
    }

    public function refreshTasks()
    {
        if ($this->project_id) {
            $query = Task::with(['project', 'taskAssignee', 'tags'])->where('project_id', $this->project_id);
            if ($this->user_id) {
                $query->whereHas('taskAssignee', function ($q) {
                    $q->where('user_id', $this->user_id);
                });
            }
            $this->tasks = $query->get()->toArray();
        } else {
            $this->tasks = [];
        }
    }

    // Fetch all statuses for Kanban columns
    public function getStatusesProperty()
    {
        return Status::orderBy('order')->get();
    }

    // Fetch all projects for filter
    public function getProjectsProperty()
    {
        return Project::all()->pluck('name', 'id');
    }

    // Method to handle filter changes
    public function updated($property)
    {
        if ($property === 'project_id') {
            $this->user_id = null;
            $project = Project::with('users')->find($this->project_id);
            $this->users = $project?->users->pluck('name', 'id')->toArray() ?? [];
            $this->refreshTasks();
            $this->js('refreshKanban', [
                'tasks' => $this->tasks,
                'users' => $this->users
            ]);
        } elseif ($property === 'user_id') {
            $this->refreshTasks();
            $this->js('refreshKanban', [
                'tasks' => $this->tasks,
                'users' => $this->users
            ]);
        }
    }

    public function taskMoved($taskId, $status, $order)
    {
        $task = Task::find($taskId);
        if ($task) {
            $task->status = $status;
            $task->save();
            $this->refreshTasks();
            $this->js('refreshKanban', [
                'tasks' => $this->tasks,
                'users' => $this->users
            ]);
        }
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

    public function taskListAction(): Action
    {
        return Action::make('taskList')
            ->hiddenLabel()
            ->tooltip('Task List')
            ->icon('heroicon-s-list-bullet')
            ->url(fn(): string => route('filament.admin.pages.task'));
    }

    public function setDueDateAction(): Action
    {
        return Action::make('setDueDate')
            ->record(fn($arguments) => $arguments['record'] ?? null)
            ->label('Set Due Date')
            ->modalWidth('md')
            ->icon('heroicon-s-calendar')
            ->modalHeading('Set Due Date')
            ->form([
                DatePicker::make('due_date')
                    ->label('Due Date')
                    ->placeholder('Select Due Date')
                    ->native(false)
                    ->minDate(now())
                    ->default(fn($record) => $record?->due_date),
            ])
            ->modalActions(function ($record) {
                return [
                    Action::make('remove_due_date')
                        ->label('Remove Due Date')
                        ->color('danger')
                        ->visible(fn($record) => $record?->due_date !== null)
                        ->action(function (Action $action, $livewire, $record) {

                            $record->update(['due_date' => null]);

                            $livewire->refreshTasks();
                            $livewire->js('refreshKanban', [
                                'tasks' => $livewire->tasks,
                                'users' => $livewire->users,
                            ]);

                            return null;
                        })
                        ->successNotificationTitle('Due date removed successfully'),

                    Action::make('submit')
                        ->label('Save')
                        ->submit('setDueDate'),
                ];
            })
            ->action(function (array $data, $record) {
                $record->update(['due_date' => $data['due_date']]);

                $this->refreshTasks();
                $this->js('refreshKanban', [
                    'tasks' => $this->tasks,
                    'users' => $this->users
                ]);
            })
            ->successNotificationTitle('Due date updated successfully');
    }


    public function assignUsersAction(): Action
    {
        return Action::make('assignUsers')
            ->record(fn($arguments) => $arguments['record'] ?? null)
            ->label('Assign Users')
            ->modalWidth('md')
            ->icon('heroicon-s-user')
            ->modalHeading('Assign Users')
            ->form([
                Select::make('taskAssignee')
                    ->label('Assignee')
                    ->multiple()
                    ->options(function ($get, $livewire) {
                        $projectId = $livewire->project_id;

                        if (!$projectId) {
                            return [];
                        }

                        return User::whereHas('projects', function ($q) use ($projectId) {
                            $q->where('project_id', $projectId)
                                ->where('is_active', 1);
                        })->pluck('name', 'id')->toArray();
                    })
                    ->preload()
                    ->searchable()
                    ->native(false)
                    ->required()
                    ->default(function ($record) {
                        if ($record && $record instanceof Task) {
                            return $record->taskAssignee->pluck('id')->toArray();
                        }
                        return [];
                    }),
            ])
            ->action(function (array $data, $record) {
                if ($record) {
                    // Get the old assignees before syncing
                    $oldAssignees = $record->taskAssignee->pluck('id')->toArray();

                    // Sync the task assignees
                    $record->taskAssignee()->sync($data['taskAssignee'] ?? []);

                    // Get the new assignees after syncing
                    $newAssignees = $data['taskAssignee'] ?? [];

                    // Refresh the tasks data and update the board without page refresh
                    $this->refreshTasks();
                    $this->js('refreshKanban', [
                        'tasks' => $this->tasks,
                        'users' => $this->users
                    ]);

                    // Send notifications to newly assigned users
                    $newlyAssignedUsers = array_diff($newAssignees, $oldAssignees);
                    foreach ($newlyAssignedUsers as $userId) {
                        UserNotification::create([
                            'title'       => 'New Task Assigned',
                            'description' => $record->title . ' assigned to you',
                            'type'        => Task::class,
                            'user_id'     => $userId,
                        ]);
                    }

                    // Log the activity
                    $project = $record->project;
                    if ($project) {
                        activity()
                            ->causedBy(getLoggedInUser())
                            ->performedOn($project)
                            ->withProperties([
                                'model' => Task::class,
                                'data'  => [
                                    'task_id' => $record->id,
                                    'task_title' => $record->title,
                                    'old_assignees' => $oldAssignees,
                                    'new_assignees' => $newAssignees,
                                ],
                            ])
                            ->useLog('Task Assignees Updated')
                            ->log('Updated assignees for task ' . $record->title);
                    }
                }
            })
            ->successNotificationTitle('Users assigned successfully');
    }

    public function createTaskAction(): Action
    {
        return CreateAction::make('createTask')
            ->label('New Task')
            ->modalWidth('2xl')
            ->icon('heroicon-s-plus')
            ->model(Task::class)
            ->schema(self::getTaskForm())
            ->modalHeading(function () {
                $projectName = Project::find($this->project_id)?->name ?? 'Project';
                return "Create New Task for {$projectName}";
            })
            ->createAnother(false)
            ->using(function (array $data) {

                $data['project_id'] = $this->project_id ?? null;
                // Set the status to the target status if provided
                if ($this->targetStatus) {
                    $data['status'] = $this->targetStatus;
                }

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

                // Reset target status after use
                $this->targetStatus = null;

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

                // Handle tags
                if (! empty($data['tags'])) {
                    $record->tags()->sync($data['tags']);
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

                // Refresh the tasks data and update the board without page refresh
                $this->refreshTasks();
                $this->js('refreshKanban', [
                    'tasks' => $this->tasks,
                    'users' => $this->users
                ]);
            })
            ->successNotificationTitle(__('messages.projects.task_created_successfully'));
    }

    public function editTaskAction(): Action
    {
        return EditAction::make('editTask')
            ->label('Edit Task')
            ->modalWidth('2xl')
            ->icon('heroicon-s-pencil')
            ->model(Task::class)
            ->schema(self::getTaskForm())
            ->modalHeading('Edit Task')
            ->record(fn($arguments) => Task::find($arguments['taskId'] ?? null))
            ->action(function (array $data, $record) {
                if (!$record) {
                    return;
                }

                // Update the task
                $record->update($data);

                // Handle task assignees
                if (!empty($data['taskAssignee'])) {
                    $record->taskAssignee()->sync($data['taskAssignee']);
                }

                // Handle tags
                if (isset($data['tags'])) {
                    $record->tags()->sync($data['tags']);
                }

                // Refresh the tasks data and update the board without page refresh
                $this->refreshTasks();
                $this->js('refreshKanban', [
                    'tasks' => $this->tasks,
                    'users' => $this->users
                ]);
            })
            ->successNotificationTitle('Task updated successfully');
    }

    public function deleteTaskAction(): Action
    {
        return DeleteAction::make('deleteTask')
            ->label('Delete Task')
            ->modalWidth('md')
            ->icon('heroicon-s-trash')
            ->modalHeading('Delete Task')
            ->modalSubheading('Are you sure you want to delete this task? This action cannot be undone.')
            ->record(fn($arguments) => Task::find($arguments['taskId'] ?? null))
            ->action(function ($record) {
                if (!$record) {
                    return;
                }

                // Delete the task
                $record->delete();

                // Refresh the tasks data and update the board without page refresh
                $this->refreshTasks();
                $this->js('refreshKanban', [
                    'tasks' => $this->tasks,
                    'users' => $this->users
                ]);
            })
            ->successNotificationTitle('Task deleted successfully');
    }

    public function addTimeEntryAction(): Action
    {
        return Action::make('addTimeEntry')
            ->label(__('messages.projects.new_time_entry'))
            ->modalWidth('2xl')
            ->icon('heroicon-o-clock')
            ->modalHeading(__('messages.projects.create_time_entry'))
            ->record(fn($arguments) => Task::find($arguments['taskId'] ?? null))
            ->form(fn($record) => $this->createTimeEntryForm($record))
            ->mountUsing(function (Task $record, $form) {
                if ($record) {
                    $form->fill([
                        'entry_type' => TimeEntry::VIA_FORM,
                        'user_id' => Auth::id(),
                        'task_id' => $record->id,
                        'project_id' => $record->project_id,
                    ]);
                }
            })
            ->action(function (array $data) {
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
            ->successNotificationTitle(__('messages.projects.time_entry_created_successfully'));
    }

    public function addTaskInStatus($id)
    {
        // Store the target status for the new task
        $this->targetStatus = $id;
        $this->mountAction('createTask');
    }

    public function setTaskDueDate($taskId)
    {
        $task = Task::find($taskId);
        if (! $task) {
            return;
        }

        $this->mountAction(
            'setDueDate',
            arguments: ['record' => $task]
        );
    }

    public function assignTaskUsers($taskId)
    {
        $task = Task::find($taskId);
        if (!$task) {
            return;
        }

        $this->mountAction('assignUsers', [
            'record' => $task,
        ]);
    }

    public function editTask($taskId)
    {
        $this->mountAction('editTask', [
            'taskId' => $taskId,
        ]);
    }

    public function deleteTask($taskId)
    {
        $this->mountAction('deleteTask', [
            'taskId' => $taskId,
        ]);
    }

    public function addTimeEntry($taskId)
    {
        $this->mountAction('addTimeEntry', [
            'taskId' => $taskId,
        ]);
    }

    public function filterForm(Schema $form): Schema
    {
        return $form
            ->schema([
                Select::make('project_id')
                    ->hiddenLabel()
                    ->options(fn() => $this->projects ?? [])
                    ->searchable()
                    ->preload()
                    ->live()
                    ->default(function () {
                        if ($this->project_id) {
                            return $this->project_id;
                        }
                        $firstProject = Project::first();
                        return $firstProject ? $firstProject->id : null;
                    })
                    ->extraAttributes(['class' => 'no-clear'])
                    ->afterStateUpdated(fn(Set $set) => $set('user_id', null)),
                Select::make('user_id')
                    ->hiddenLabel()
                    ->options(fn() => $this->users ?? [])
                    ->searchable()
                    ->preload()
                    ->live(),
            ])
            ->columns([
                'default' => 1,
                'sm' => 2,
            ]);
    }

    public static function getTaskForm(): array
    {
        return [
            Group::make([

                TextInput::make('title')
                    ->label('Title')
                    ->placeholder('Title')
                    ->required(),

                Select::make('priority')
                    ->label('Priority')
                    ->options(Task::PRIORITY)
                    ->searchable(),

                Select::make('taskAssignee')
                    ->label('Assignee')
                    ->multiple()
                    ->options(function (callable $get, $livewire) {
                        $projectId = $livewire->project_id;

                        if (!$projectId) {
                            return [];
                        }

                        return User::whereHas('projects', function ($q) use ($projectId) {
                            $q->where('project_id', $projectId)
                                ->where('is_active', 1);
                        })->pluck('name', 'id')->toArray();
                    })
                    ->preload()
                    ->searchable()
                    ->native(false)
                    ->required()
                    ->afterStateHydrated(function (callable $set, callable $get) {
                        $taskId = $get('id');
                        if ($taskId) {
                            $task = Task::with('taskAssignee')->find($taskId);
                            if ($task) {
                                $set('taskAssignee', $task->taskAssignee->pluck('id')->toArray());
                            }
                        }
                    }),

                DatePicker::make('due_date')
                    ->label('Due Date')
                    ->placeholder('SelectDue Date')
                    ->native(false)
                    ->minDate(now()),

                TextInput::make('estimate_time')
                    ->label('Estimate Time')
                    ->reactive()
                    ->placeholder('Enter estimate')
                    ->default(0)
                    ->afterStateHydrated(function ($set, $get) {
                        if (! $get('estimate_time_type')) {
                            $set('estimate_time_type', Task::IN_HOURS);
                        }
                    })
                    ->extraInputAttributes(function ($get) {
                        return [
                            'type' => $get('estimate_time_type') === Task::IN_HOURS ? 'time' : 'number',
                            'min' => 0,
                        ];
                    })
                    ->suffixActions([
                        Action::make('set_hours')
                            ->button()
                            ->label('In Hours')
                            ->size('xs')
                            ->color(fn($get) => $get('estimate_time_type') === Task::IN_HOURS ? 'primary' : 'secondary')
                            ->action(function ($set) {
                                $set('estimate_time_type', Task::IN_HOURS);
                                $set('estimate_time', null);
                            }),

                        Action::make('set_days')
                            ->button()
                            ->label('In Days')
                            ->size('xs')
                            ->color(fn($get) => $get('estimate_time_type') === Task::IN_DAYS ? 'primary' : 'secondary')
                            ->action(function ($set) {
                                $set('estimate_time_type', Task::IN_DAYS);
                                $set('estimate_time', null);
                            }),
                    ]),

                Select::make('tags')
                    ->label('Tags')
                    ->multiple()
                    ->options(fn() => Tag::query()->pluck('name', 'id'))
                    // ->relationship('tags', 'name')
                    ->preload()
                    ->searchable()
                    ->native(false)
                    ->afterStateHydrated(function (callable $set, callable $get) {
                        $taskId = $get('id');
                        if ($taskId) {
                            $task = Task::with('tags')->find($taskId);
                            if ($task) {
                                $set('tags', $task->tags->pluck('id')->toArray());
                            }
                        }
                    }),

                RichEditor::make('description')
                    ->label('Description')
                    ->placeholder('Description')
                    ->columnSpanFull()
                    ->extraAttributes(['style' => 'min-height: 200px;'])
                    ->disableToolbarButtons(['table', 'attachFiles']),

            ])
                ->columns(2)
        ];
    }

    public function createTimeEntryForm($record = null)
    {
        $isDisabled = $record !== null;
        return [
            Hidden::make('entry_type')
                ->default(TimeEntry::VIA_FORM),

            Hidden::make('user_id')
                ->default(Auth::id()),

            Hidden::make('task_id')
                ->default($record ? $record->id : null),

            Group::make([
                Group::make([
                    Select::make('project_id')
                        ->label(__('messages.projects.project'))
                        ->options(function () {
                            return Project::whereNull('deleted_at')
                                ->where('status', 1)
                                ->whereHas('users', function ($q) {
                                    $q->where('user_id', Auth::id());
                                })
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->native(false)
                        ->live()
                        ->disabled($isDisabled)
                        ->afterStateUpdated(function (callable $set, callable $get) {
                            $set('task_id', null);
                        })
                        ->default($record ? $record->project_id : null),

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
                        ->native(false)
                        ->disabled()
                        ->dehydrated(true)
                        ->default($record ? $record->title : null),

                    Select::make('activity_type_id')
                        ->label(__('messages.settings.activity_type'))
                        ->options(function () {
                            return \App\Models\ActivityType::pluck('name', 'id');
                        })
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

    public function infoAction(): Action
    {
        return ViewAction::make('info')
            ->record(fn($arguments) => Task::find($arguments['task']))
            ->tooltip(__('messages.common.view'))
            ->icon('heroicon-s-information-circle')
            ->color('info')
            ->iconButton()
            ->modalWidth('4xl')
            ->modalHeading(__('messages.projects.task_details'))
            ->schema([
                Group::make()
                    ->schema([
                        Group::make()
                            ->schema([
                                TextEntry::make('title')
                                    ->hiddenLabel()
                                    ->html()
                                    ->extraAttributes(['style' => 'font-size: 1.25rem; font-weight: 600;']),

                                TextEntry::make('description')
                                    ->label(__('messages.common.description'))
                                    ->html()
                                    ->placeholder('N/A'),

                                Fieldset::make(__('messages.projects.attachments'))
                                    ->schema([
                                        Action::make('add_attachment')
                                            ->label(__('messages.projects.new_attachment'))
                                            ->icon('heroicon-s-plus')
                                            ->modalHeading(__('messages.projects.upload_attachment'))
                                            ->modalWidth('lg')
                                            ->form([
                                                SpatieMediaLibraryFileUpload::make('upload_file')
                                                    ->label(__('messages.projects.select_file'))
                                                    ->directory('task-attachments')
                                                    ->preserveFilenames()
                                                    ->maxSize(10240)
                                                    ->required(),
                                            ])
                                            ->action(function (array $data, $record) {
                                                if ($record && !empty($data['upload_file'])) {
                                                    $record
                                                        ->addMedia($data['upload_file']->getRealPath())
                                                        ->usingFileName($data['upload_file']->getClientOriginalName())
                                                        ->toMediaCollection('attachments');
                                                }
                                            }),

                                        Repeater::make('attachments')
                                            ->label(__('messages.projects.all_attachments'))
                                            ->default(function ($record) {
                                                if (!$record) return [];

                                                return $record->getMedia('attachments')->map(function ($media) {
                                                    return [
                                                        'file_name' => $media->file_name,
                                                        'file_url'  => $media->getFullUrl(),
                                                        'created_at' => $media->created_at->diffForHumans(),
                                                    ];
                                                })->toArray();
                                            })
                                            ->schema([
                                                Group::make()->schema([
                                                    ImageEntry::make('file_url')
                                                        ->circular()
                                                        ->imageHeight(40)
                                                        ->label(''),

                                                    TextEntry::make('file_name')
                                                        ->label('')
                                                        ->extraAttributes(['style' => 'font-weight: 600;']),

                                                    TextEntry::make('created_at')
                                                        ->label('')
                                                        ->extraAttributes(['style' => 'font-size: 12px; color: #888;']),
                                                ]),
                                            ])
                                            ->columns(1)
                                    ])
                                    ->columns(1),

                                Fieldset::make(__('messages.projects.comments'))
                                    ->schema([
                                        Action::make('add_comment')
                                            ->label(__('messages.projects.new_comment'))
                                            ->icon('heroicon-s-plus')
                                            ->modalHeading(__('messages.projects.create_comment'))
                                            ->modalWidth('xl')
                                            ->form([
                                                RichEditor::make('new_comment')
                                                    ->label(__('messages.projects.comment'))
                                                    ->required()
                                                    ->columnSpanFull()
                                                    ->placeholder(__('messages.projects.comment'))
                                                    ->extraAttributes(['style' => 'min-height: 200px;'])
                                                    ->toolbarButtons([
                                                        ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link'],
                                                        ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                                                        ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
                                                        ['undo', 'redo'],
                                                    ]),
                                            ])
                                            ->action(function (array $data, $record): void {
                                                if ($record && !empty($data['new_comment']) && $record instanceof Task) {
                                                    Comment::create([
                                                        'comment' => $data['new_comment'],
                                                        'task_id' => $record->id,
                                                        'created_by' => Auth::id(),
                                                    ]);
                                                }
                                            }),

                                        Repeater::make('comment')
                                            ->label(__('messages.projects.comments'))
                                            ->default(function ($record) {
                                                if (!$record) return [];

                                                return $record->comments->map(function ($item) {
                                                    return [
                                                        'user_name'  => $item->createdUser->name ?? __('messages.projects.unknown_user'),
                                                        'avatar'     => $item->user_avatar,
                                                        'comment'    => $item->comment,
                                                        'created_at' => $item->created_at->diffForHumans(),
                                                    ];
                                                })->toArray();
                                            })
                                            ->schema([
                                                Group::make()->schema([
                                                    ImageEntry::make('avatar')
                                                        ->circular()
                                                        ->imageHeight(35)
                                                        ->label(''),

                                                    TextEntry::make('user_name')
                                                        ->label('')
                                                        ->extraAttributes(['style' => 'font-weight: 600;']),

                                                    TextEntry::make('created_at')
                                                        ->label('')
                                                        ->extraAttributes(['style' => 'font-size: 12px; color: #888;']),

                                                    TextEntry::make('comment')
                                                        ->label('')
                                                        ->html(),
                                                ]),
                                            ])
                                            ->columns(1)
                                    ])
                                    ->columns(1),
                            ])
                            ->columnSpan(2),

                        Group::make()
                            ->schema([
                                ImageEntry::make('task_assignees')
                                    ->label(__('messages.projects.assignee'))
                                    ->default(function ($record) {
                                        if (!$record) return [];

                                        $users = User::whereIn('id', function ($query) use ($record) {
                                            $query->select('user_id')
                                                ->from('task_assignees')
                                                ->where('task_id', $record->id);
                                        })->get();

                                        return $users->map(function ($user) {
                                            return $user->img_avatar
                                                ?? "https://ui-avatars.com/api/?name=" . urlencode($user->name) . "&background=random";
                                        })->toArray();
                                    })
                                    ->stacked()
                                    ->circular()
                                    ->limit(6)
                                    ->limitedRemainingText()
                                    ->imageHeight(40)
                                    ->extraAttributes([
                                        'style' => 'display: flex;',
                                    ]),

                                TextEntry::make('duration')
                                    ->label(__('messages.projects.time_tracking'))
                                    ->getStateUsing(function ($record) {
                                        $totalMinutes = $record->timeEntries->sum('duration');
                                        $hours = floor($totalMinutes / 60);
                                        $minutes = $totalMinutes % 60;

                                        $time = sprintf('%02d:%02d M', $hours, $minutes);

                                        return "<div class='text-center font-bold text-xl'>{$time}</div>";
                                    })
                                    ->formatStateUsing(fn($state) => $state)
                                    ->html(),

                                Fieldset::make(__('messages.settings.settings'))
                                    ->schema([
                                        TextEntry::make('created_at')
                                            ->label(__('messages.projects.start_at'))
                                            ->inlineLabel()
                                            ->formatStateUsing(function ($state) {
                                                return Carbon::parse($state)->format('jS M, Y');
                                            }),

                                        TextEntry::make('due_date')
                                            ->label(__('messages.projects.due_date'))
                                            ->inlineLabel()
                                            ->formatStateUsing(function ($state) {
                                                if (!$state) {
                                                    return 'N/A';
                                                }

                                                $date = Carbon::parse($state);
                                                return $date->format('jS M, Y');
                                            })
                                            ->placeholder('N/A'),

                                        TextEntry::make('status')
                                            ->label(__('messages.common.status'))
                                            ->inlineLabel()
                                            ->formatStateUsing(fn($state) => Status::where('status', $state)->value('name') ?? $state),

                                        TextEntry::make('priority')
                                            ->label(__('messages.projects.priority'))
                                            ->inlineLabel()
                                            ->default('N/A')
                                            ->formatStateUsing(fn($state) => Task::PRIORITY[$state] ?? $state),
                                    ])
                                    ->columns(1),

                                Fieldset::make(__('messages.projects.information'))
                                    ->schema([
                                        TextEntry::make('created_by')
                                            ->label(__('messages.projects.created_by'))
                                            ->inlineLabel()
                                            ->formatStateUsing(function ($state) {
                                                return User::find($state)->name;
                                            }),

                                        TextEntry::make('created_at')
                                            ->label(__('messages.projects.created_on'))
                                            ->inlineLabel()
                                            ->formatStateUsing(function ($state) {
                                                return Carbon::parse($state)->format('jS M, Y');
                                            }),

                                        TextEntry::make('time_tracking')
                                            ->label(__('messages.projects.time_tracking'))
                                            ->inlineLabel()
                                            ->getStateUsing(function ($record) {
                                                $totalMinutes = $record->timeEntries->sum('duration');
                                                $hours = floor($totalMinutes / 60);
                                                $minutes = $totalMinutes % 60;
                                                return sprintf('%02d:%02d M', $hours, $minutes);
                                            })
                                            ->default('00:00 m'),

                                        TextEntry::make('project.name')
                                            ->label(__('messages.projects.project'))
                                            ->inlineLabel(),
                                    ])
                                    ->columns(1),
                            ])
                            ->columnSpan(1),
                    ])
                    ->columnSpanFull()
                    ->columns(3),
            ]);
    }

    public function showTags(): Action
    {
        return ViewAction::make('showTags')
            ->record(fn($arguments) => Task::with('tags')->find($arguments['task']))
            ->modalHeading(fn($record) => $record->title . ' Tags')
            ->modalWidth('md')
            ->schema([
                Group::make([
                    TextEntry::make('tags_display')
                        ->hiddenLabel()
                        ->html()
                        ->state(fn($record) => $record)   // pass the record manually
                        ->formatStateUsing(function ($record) {
                            $tagsHtml = '';

                            foreach ($record->tags as $tag) {
                                $color =  '#464d5d';

                                $tagsHtml .= "<div style='display: inline-flex; align-items: center; background-color: {$color}; padding: 4px 10px; border-radius: 16px; font-weight: 400; margin: 4px;'>
                                                {$tag->name}
                                            </div>";
                            }

                            return "<div style='display: flex; flex-wrap: wrap; gap: 3px; padding: 3px 0;'>{$tagsHtml}</div>";
                        })
                ])
            ]);
    }

    public function viewTaskDetails($taskId)
    {
        $this->mountAction('info', [
            'task' => $taskId,
        ]);
    }

    public function viewTaskTags($taskId)
    {
        $this->mountAction('showTags', [
            'task' => $taskId,
        ]);
    }
}
