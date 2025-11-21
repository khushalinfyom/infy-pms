<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Task;
use App\Models\UserNotification;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\Page;
use Illuminate\View\View;

class ManageTasks extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = TaskResource::class;

    protected string $view = 'filament.resources.tasks.pages.manage-tasks';

    public ?array $data = [];

    public function getHeading(): string
    {
        return 'Tasks';
    }

    public function mount(): void
    {
        $this->data = $this->getTasks();
    }

    public function getTasks(): array
    {
        return Task::with(['project.users'])->latest()->take(10)->get()->toArray();
    }

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
                    ->form(fn($form) => TaskResource::form($form))
                    ->using(function (array $data) {

                        $data['estimate_time_type'] = $data['estimate_time_type'] ?? Task::IN_HOURS;
                        $data['estimate_time'] = $data['estimate_time'] ?? null;

                        if (empty($data['task_number']) && isset($data['project_id'])) {
                            $data['task_number'] = Task::generateUniqueTaskNumber($data['project_id']);
                        }

                        $data['created_by'] = getLoggedInUser()->id;

                        $task = Task::create($data);

                        if (!empty($data['taskAssignee']) && is_array($data['taskAssignee'])) {
                            $task->taskAssignee()->sync($data['taskAssignee']);
                        }

                        return $task;
                    })
                    ->after(function ($record, $data) {
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
}
