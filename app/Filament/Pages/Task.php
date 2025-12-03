<?php

namespace App\Filament\Pages;

use App\Enums\AdminPanelSidebar;
use App\Models\Task as TaskModel;
use App\Models\User;
use BackedEnum;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;
use Livewire\Attributes\On;

class Task extends Page
{
    protected string $view = 'filament.pages.task';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?int $navigationSort = AdminPanelSidebar::TASKS->value;

    protected static string|\UnitEnum|null $navigationGroup = 'Tasks';

    public function getTitle(): string
    {
        return 'My Tasks';
    }

    public static function getNavigationLabel(): string
    {
        return 'My Tasks';
    }

    public function getTasks(): Collection
    {
        return TaskModel::whereHas('taskAssignee', function ($q) {
            $q->where('user_id', auth()->id());
        })->where('status', '!=', TaskModel::STATUS_COMPLETED)
            ->with(['project', 'taskAssignee', 'timeEntries'])
            ->get();
    }

    #[On('markTaskAsCompleted')]
    public function markTaskAsCompleted(int $taskId): void
    {
        $task = TaskModel::findOrFail($taskId);

        // Check if the authenticated user is assigned to this task
        $isAssigned = $task->taskAssignee->pluck('id')->contains(auth()->id());
        if (!$isAssigned) {
            Notification::make()
                ->title('Unauthorized')
                ->body('You are not assigned to this task.')
                ->danger()
                ->send();
            return;
        }

        $task->update([
            'status' => TaskModel::STATUS_COMPLETED,
            'completed_on' => Carbon::now(),
        ]);

        Notification::make()
            ->title('Task Completed')
            ->body('The task has been marked as completed.')
            ->success()
            ->send();

        // Refresh the component to show updated data
        $this->dispatch('taskUpdated');
    }

    #[On('setTaskDueDate')]
    public function setTaskDueDate(int $taskId, string $dueDate): void
    {
        $task = TaskModel::findOrFail($taskId);

        // Check if the authenticated user is assigned to this task
        $isAssigned = $task->taskAssignee->pluck('id')->contains(auth()->id());
        if (!$isAssigned) {
            Notification::make()
                ->title('Unauthorized')
                ->body('You are not assigned to this task.')
                ->danger()
                ->send();
            return;
        }

        $task->update([
            'due_date' => $dueDate,
        ]);

        Notification::make()
            ->title('Due Date Set')
            ->body('The due date has been set successfully.')
            ->success()
            ->send();

        // Refresh the component to show updated data
        $this->dispatch('taskUpdated');
    }
}
