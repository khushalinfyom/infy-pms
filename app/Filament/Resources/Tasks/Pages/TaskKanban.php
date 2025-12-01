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
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;

class TaskKanban extends Page implements HasActions, HasForms
{
    use InteractsWithActions, InteractsWithForms;

    protected static string $resource = TaskResource::class;

    protected string $view = 'filament.resources.tasks.pages.task-kanban';

    // Properties to hold filter values
    public ?int $project_id = null;
    public ?int $user_id = null;

    public function mount(): void
    {
        // Set default project to first available project
        $firstProject = Project::first();
        if ($firstProject) {
            $this->project_id = $firstProject->id;
        }
    }

    // Fetch tasks based on filters
    public function getTasksProperty()
    {
        // Always require project_id
        if (!$this->project_id) {
            return collect(); // Return empty collection if no project selected
        }

        $query = Task::with(['project', 'taskAssignee']);

        if ($this->project_id) {
            $query->where('project_id', $this->project_id);
        }

        if ($this->user_id) {
            $query->whereHas('taskAssignee', function ($q) {
                $q->where('user_id', $this->user_id);
            });
        }

        return $query->get();
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

    // Fetch all users for filter
    public function getUsersProperty()
    {
        return User::where('is_active', 1)->pluck('name', 'id');
    }

    // Method to handle filter changes
    public function updated($property)
    {
        // if ($property === 'project_id') {
        //     dd('Selected Project:', $this->project_id);
        // }
    }

    public function createTaskAction(): Action
    {
        return CreateAction::make('createTask')
            ->label('New Task')
            ->modalWidth('2xl')
            ->icon('heroicon-s-plus')
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
                    ->options($this->projects)
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required() // Make project_id required
                    ->default(function () {
                        // Always ensure we have a default project
                        if ($this->project_id) {
                            return $this->project_id;
                        }
                        $firstProject = Project::first();
                        return $firstProject ? $firstProject->id : null;
                    }),
                Select::make('user_id')
                    ->hiddenLabel()
                    ->options($this->users)
                    ->searchable()
                    ->preload()
                    ->live(),
            ])
            ->columns([
                'default' => 2,
                'sm' => 2,
            ]);
    }

    public static function getTaskForm(): array
    {
        return [
            // ...
        ];
    }
}
