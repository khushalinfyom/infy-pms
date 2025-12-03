<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class TaskKanban extends Page implements HasActions, HasForms
{
    use InteractsWithActions, InteractsWithForms;

    protected static string $resource = TaskResource::class;

    protected string $view = 'filament.resources.tasks.pages.task-kanban';

    protected static ?string $title = '';

    public ?array $tasks = [];

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
            $query = Task::with(['project', 'taskAssignee'])->where('project_id', $this->project_id);
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

    public function taskListAction(): Action
    {
        return Action::make('taskList')
            ->label('Task List')
            ->icon('heroicon-s-list-bullet')
            ->url(TaskResource::getUrl('index'));
    }

    public function createTaskAction(): Action
    {
        return CreateAction::make('createTask')
            ->label('New Task')
            ->modalWidth('2xl')
            ->icon('heroicon-s-plus')
            ->schema([
                Group::make(self::getTaskForm())->columns(2),
            ])
            ->modalHeading('Create Task')
            ->createAnother(false);
    }

    public function addTaskInStatus($id)
    {
        $this->mountAction('createTask');
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
                ->options(function (callable $get) {
                    $projectId = $get('project_id');

                    if (!$projectId) {
                        return [];
                    }

                    return User::whereHas('projects', function ($q) use ($projectId) {
                        $q->where('project_id', $projectId);
                    })->pluck('name', 'id')->toArray();
                })
                ->preload()
                ->searchable()
                ->native(false)
                ->required(fn(callable $get) => !empty($get('project_id')))
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
                // ->relationship('tags', 'name')
                ->preload()
                ->searchable()
                ->native(false),

            RichEditor::make('description')
                ->label('Description')
                ->placeholder('Description')
                ->columnSpanFull()
                ->extraAttributes(['style' => 'min-height: 200px;'])
                ->disableToolbarButtons(['table', 'attachFiles']),
        ];
    }
}
