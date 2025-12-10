<?php

namespace App\Livewire;

use App\Models\ActivityType;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Exception;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class StopWatchModal extends Component implements HasForms
{
    use InteractsWithForms;

    protected $listeners = ['resetFormData'];

    public $project_id = null;
    public $task_id = null;
    public $activity_id = null;
    public $activeTimeEntry = null;
    public $elapsedTime = 0;
    public $note = '';

    public function mount()
    {
        $this->activeTimeEntry = TimeEntry::where('user_id', Auth::id())
            ->whereNull('end_time')
            ->first();

        if ($this->activeTimeEntry) {
            $this->elapsedTime = Carbon::parse($this->activeTimeEntry->start_time)->diffInSeconds(Carbon::now());
        }
    }

    public function render()
    {
        return view('livewire.stop-watch-modal');
    }

    public function updateTimer()
    {
        if ($this->activeTimeEntry) {
            $this->elapsedTime = Carbon::parse($this->activeTimeEntry->start_time)->diffInSeconds(Carbon::now());
        }
    }

    public function form(Schema $form): Schema
    {
        if ($this->activeTimeEntry) {
            return $form->schema([]);
        }

        return $form
            ->model(TimeEntry::class)
            ->schema([
                Select::make('project_id')
                    ->label('Project')
                    ->options(Project::all()->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->afterStateUpdated(function (callable $set) {
                        $set('task_id', null);
                    })
                    ->live(),

                Select::make('task_id')
                    ->label('Task')
                    ->options(function (callable $get) {
                        $projectId = $get('project_id');
                        if ($projectId) {
                            return Task::where('project_id', $projectId)
                                ->whereNull('deleted_at')
                                ->where('status', '!=', 1)
                                ->pluck('title', 'id');
                        }
                        return [];
                    })
                    ->live()
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required(),

                Select::make('activity_id')
                    ->label('Activity Type')
                    ->options(ActivityType::all()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required(),

                Textarea::make('note')
                    ->label('Note')
                    ->placeholder('Note')
                    ->required()
                    ->columnSpanFull()

            ]);
    }

    public function save()
    {
        $this->form->validate();
        try {
            TimeEntry::create([
                'project_id' => $this->project_id,
                'task_id' => $this->task_id,
                'activity_type_id' => $this->activity_id,
                'user_id' => Auth::id(),
                'duration' => 0,
                'start_time' => Carbon::now(),
                'entry_type' => TimeEntry::STOPWATCH,
                'note' => $this->note,
            ]);

            $this->reset(['project_id', 'task_id', 'activity_id']);

            $this->activeTimeEntry = TimeEntry::where('user_id', Auth::id())
                ->whereNull('end_time')
                ->first();

            if ($this->activeTimeEntry) {
                $this->elapsedTime = 0;
            }
        } catch (Exception $exception) {
            Notification::make()
                ->danger()
                ->title('Error starting time entry: ' . $exception->getMessage())
                ->send();
        }
    }

    public function clockOut()
    {
        try {
            if ($this->activeTimeEntry) {
                $this->activeTimeEntry->update([
                    'end_time' => Carbon::now(),
                ]);

                $startTime = Carbon::parse($this->activeTimeEntry->start_time);
                $endTime = Carbon::now();
                $duration = $startTime->diffInMinutes($endTime);

                $this->activeTimeEntry->update([
                    'duration' => $duration,
                ]);

                $this->activeTimeEntry = null;
                $this->elapsedTime = 0;

                Notification::make()
                    ->success()
                    ->title('Time entry stored successfully!')
                    ->send();
            }
        } catch (Exception $exception) {
            Notification::make()
                ->danger()
                ->title('Error ending time entry: ' . $exception->getMessage())
                ->send();
        }
    }
}
