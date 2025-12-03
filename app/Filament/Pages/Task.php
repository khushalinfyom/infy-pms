<?php

namespace App\Filament\Pages;

use App\Enums\AdminPanelSidebar;
use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Comment;
use App\Models\Task as TaskModel;
use App\Models\Project;
use App\Models\Status;
use App\Models\Tag;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\UserNotification;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;

class Task extends Page implements HasActions
{
    use InteractsWithActions;

    protected string $view = 'filament.pages.task';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?int $navigationSort = AdminPanelSidebar::TASKS->value;

    public function getTitle(): string
    {
        return 'Task';
    }

    public static function getNavigationLabel(): string
    {
        return 'Task';
    }

    public function getTasks(): Collection
    {
        return TaskModel::whereHas('taskAssignee', function ($q) {
            $q->where('user_id', Auth::id());
        })->where('status', '!=', TaskModel::STATUS_COMPLETED)
            ->with(['project', 'taskAssignee', 'timeEntries'])
            ->orderBy('id', 'desc')
            ->get();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kanban')
                ->label('Kanban')
                ->icon('heroicon-s-view-columns')
                ->url(TaskResource::getUrl('kanban')),

            ActionGroup::make([

                CreateAction::make()
                    ->label(__('messages.projects.new_task'))
                    ->modalWidth('2xl')
                    ->icon('heroicon-s-plus')
                    ->modalHeading(__('messages.projects.create_task'))
                    ->createAnother(false)
                    ->form($this->getTaskForm())
                    ->using(function (array $data) {

                        $selectedType = $data['estimate_time_type'] ?? null;

                        if ($selectedType === null) {
                            $data['estimate_time_type'] = TaskModel::IN_HOURS;
                        } else {
                            $data['estimate_time_type'] = $selectedType;
                        }

                        if (empty($data['estimate_time'])) {
                            $data['estimate_time'] = null;
                        } else {
                            if ($data['estimate_time_type'] == TaskModel::IN_HOURS) {
                                if (is_string($data['estimate_time'])) {
                                } else {
                                    $data['estimate_time'] = '00:00';
                                }
                            } else {
                                $data['estimate_time'] = is_numeric($data['estimate_time']) ? $data['estimate_time'] : 0;
                            }
                        }

                        if (empty($data['task_number']) && isset($data['project_id'])) {
                            $data['task_number'] = TaskModel::generateUniqueTaskNumber($data['project_id']);
                        }

                        $data['created_by'] = Auth::id();

                        return TaskModel::create($data);
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
                                'type'        => TaskModel::class,
                                'user_id'     => $id,
                            ]);
                        }

                        $project = $record->project;

                        if ($project) {
                            activity()
                                ->causedBy(getLoggedInUser())
                                ->performedOn($project)
                                ->withProperties([
                                    'model' => TaskModel::class,
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

    public function getTaskForm(): array
    {
        return [
            Group::make([

                TextInput::make('title')
                    ->label(__('messages.projects.title'))
                    ->placeholder(__('messages.projects.title'))
                    ->required(),

                Select::make('project_id')
                    ->label(__('messages.projects.project'))
                    ->options(Project::all()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->reactive()
                    ->required()
                    ->afterStateUpdated(function (callable $set, callable $get) {
                        $set('taskAssignee', null);
                    }),

                Select::make('priority')
                    ->label(__('messages.projects.priority'))
                    ->options(TaskModel::PRIORITY)
                    ->searchable(),

                Select::make('taskAssignee')
                    ->label(__('messages.projects.assignee'))
                    ->multiple()
                    ->options(function (callable $get) {
                        $projectId = $get('project_id');

                        if (!$projectId) {
                            return [];
                        }

                        return User::whereHas('projects', function ($q) use ($projectId) {
                            $q->where('project_id', $projectId)
                                ->where('is_active', 1);
                        })->pluck('name', 'id');
                    })
                    ->preload()
                    ->searchable()
                    ->native(false)
                    ->required(fn(callable $get) => !empty($get('project_id'))),

                DatePicker::make('due_date')
                    ->label(__('messages.projects.due_date'))
                    ->placeholder(__('messages.projects.due_date'))
                    ->native(false)
                    ->minDate(now()),

                TextInput::make('estimate_time')
                    ->label(__('messages.projects.estimate_time'))
                    ->reactive()
                    ->placeholder(__('messages.projects.estimate_time'))
                    ->default(0)
                    ->afterStateHydrated(function ($set, $get) {
                        if (! $get('estimate_time_type')) {
                            $set('estimate_time_type', TaskModel::IN_HOURS);
                        }
                    })
                    ->extraInputAttributes(function ($get) {
                        return [
                            'type' => $get('estimate_time_type') === TaskModel::IN_HOURS ? 'time' : 'number',
                            'min' => 0,
                        ];
                    })
                    ->suffixActions([
                        Action::make('set_hours')
                            ->button()
                            ->label(__('messages.projects.in_hours'))
                            ->size('xs')
                            ->color(fn($get) => $get('estimate_time_type') === TaskModel::IN_HOURS ? 'primary' : 'secondary')
                            ->action(function ($set) {
                                $set('estimate_time_type', TaskModel::IN_HOURS);
                                $set('estimate_time', null);
                            }),

                        Action::make('set_days')
                            ->button()
                            ->label(__('messages.projects.in_days'))
                            ->size('xs')
                            ->color(fn($get) => $get('estimate_time_type') === TaskModel::IN_DAYS ? 'primary' : 'secondary')
                            ->action(function ($set) {
                                $set('estimate_time_type', TaskModel::IN_DAYS);
                                $set('estimate_time', null);
                            }),
                    ]),

                Select::make('tags')
                    ->label(__('messages.settings.tags'))
                    ->multiple()
                    ->options(Tag::all()->pluck('name', 'id'))
                    ->preload()
                    ->searchable()
                    ->native(false),

                RichEditor::make('description')
                    ->label(__('messages.common.description'))
                    ->placeholder('Description')
                    ->columnSpanFull()
                    ->extraAttributes(['style' => 'min-height: 200px;'])
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

    public function createTimeEntryForm($record = null)
    {
        $isDisabled = $record !== null;
        return [

            Hidden::make('entry_type')
                ->default(TimeEntry::VIA_FORM),

            Hidden::make('user_id')
                ->default(Auth::id()),

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

                            return TaskModel::where('project_id', $projectId)
                                ->whereNull('deleted_at')
                                ->where('status', '!=', 1)
                                ->pluck('title', 'id')
                                ->toArray();
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->native(false)
                        ->disabled($isDisabled)
                        ->dehydrated(true)
                        ->default($record ? $record->id : null),

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

    public function infoAction(): Action
    {
        return ViewAction::make('info')
            ->record(fn($arguments) => TaskModel::find($arguments['task']))
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
                                                if ($record && !empty($data['new_comment']) && $record instanceof TaskModel) {
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

                                        $users = \App\Models\User::whereIn('id', function ($query) use ($record) {
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
                                            ->formatStateUsing(fn($state) => TaskModel::PRIORITY[$state] ?? $state),

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
            ])
        ;
    }

    public function dueDateAction(): Action
    {
        return Action::make('dueDate')
            ->record(fn($arguments) => TaskModel::find($arguments['task']))
            ->iconButton()
            ->tooltip(__('messages.projects.set_due_date'))
            ->icon('heroicon-s-calendar')
            ->color('danger')
            ->modalWidth('md')
            ->modalHeading(__('messages.projects.add_due_date'))
            ->schema([
                DatePicker::make('due_date')
                    ->label(__('messages.projects.due_date'))
                    ->required()
                    ->native(false)
                    ->placeholder(__('messages.projects.due_date'))
                    ->minDate(Carbon::now()->subDays(1)),
            ])
            ->action(function ($record, $data) {
                $record->update($data);
            })
            ->visible(function ($record) {
                return $record->due_date == null;
            })
            ->successNotificationTitle(__('messages.projects.due_date_added_successfully'));
    }

    public function completeAction(): Action
    {
        return Action::make('complete')
            ->record(fn($arguments) => TaskModel::find($arguments['task']))
            ->tooltip(__('messages.projects.mark_as_completed'))
            ->icon('heroicon-s-check-circle')
            ->color('success')
            ->iconButton()
            ->action(function ($record) {
                $record->update([
                    'completed_on' => Carbon::now(),
                    'status' => TaskModel::STATUS_COMPLETED,
                ]);
            })
            ->visible(function ($record) {
                return $record->status != TaskModel::STATUS_COMPLETED;
            })
            ->successNotificationTitle(__('messages.projects.task_completed_successfully'));
    }

    public function editTaskAction(): Action
    {
        return EditAction::make('editTask')
            ->record(fn($arguments) => TaskModel::find($arguments['task']))
            ->iconButton()
            ->tooltip(__('messages.common.edit'))
            ->icon('heroicon-o-pencil-square')
            ->modalWidth('4xl')
            ->modalHeading(__('messages.projects.edit_task'))
            ->schema($this->getTaskForm())
            ->mountUsing(function (TaskModel $record, $form) {
                $taskAssignees = $record->taskAssignee()->pluck('user_id')->toArray();
                $formData = $record->toArray();
                $formData['taskAssignee'] = $taskAssignees;
                $form->fill($formData);
            })
            ->after(function ($record, array $data) {
                if (isset($data['taskAssignee'])) {
                    $record->taskAssignee()->sync($data['taskAssignee']);
                }
            })
            ->successNotificationTitle(__('messages.projects.task_updated_successfully'));
    }

    public function taskEntryAction(): Action
    {
        return Action::make('taskEntry')
            ->record(fn($arguments) => TaskModel::find($arguments['task']))
            ->label(__('messages.projects.new_time_entry'))
            ->tooltip(__('messages.projects.new_time_entry'))
            ->iconButton()
            ->icon('heroicon-o-clock')
            ->color('info')
            ->modalWidth('2xl')
            ->modalHeading(__('messages.projects.create_time_entry'))
            ->form(fn($record) => $this->createTimeEntryForm($record))
            ->mountUsing(function (TaskModel $record, $form) {
                $formData = [
                    'entry_type' => TimeEntry::VIA_FORM,
                    'user_id' => Auth::id(),
                    'project_id' => $record->project_id,
                    'task_id' => $record->id,
                ];
                $form->fill($formData);
            })
            ->action(function (array $data) {
                if (empty($data['duration'])) {
                    $start = Carbon::parse($data['start_time']);
                    $end = Carbon::parse($data['end_time']);
                    $data['duration'] = round($start->diffInSeconds($end) / 60, 2);
                }

                return TimeEntry::create($data);
            })
            ->visible(authUserHasPermission('manage_time_entries'))
            ->successNotificationTitle(__('messages.projects.time_entry_created_successfully'));
    }

    public function deleteTaskAction(): Action
    {
        return
            DeleteAction::make('deleteTask')
            ->record(fn($arguments) => TaskModel::find($arguments['task']))
            ->iconButton()
            ->icon('heroicon-o-trash')
            ->tooltip(__('messages.common.delete'))
            ->modalHeading(__('messages.projects.delete_task'))
            ->before(function ($record) {
                $record->update([
                    'deleted_by' => auth()->id(),
                ]);
            })
            ->successNotificationTitle(__('messages.projects.task_deleted_successfully'));
    }

    public function viewDetailsAction(): Action
    {
        return Action::make('details')
            ->record(fn($arguments) => TaskModel::find($arguments['task']))
            ->iconButton()
            ->tooltip(__('messages.projects.details'))
            ->icon('heroicon-o-arrow-right-circle')
            ->url(fn($record) => TaskResource::getUrl('taskdetails', ['record' => $record->id]));
    }
}
