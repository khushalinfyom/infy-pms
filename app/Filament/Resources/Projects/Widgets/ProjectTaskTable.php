<?php

namespace App\Filament\Resources\Projects\Widgets;

use App\Models\Comment;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use App\Models\UserNotification;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

class ProjectTaskTable extends TableWidget
{
    protected static ?string $heading = '';

    public ?Model $record = null;

    public function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->paginated([10, 25, 50, 100])
            ->defaultSort('id', 'desc')
            ->recordActionsColumnLabel('Actions')
            ->emptyStateHeading(function ($livewire) {
                if (empty($livewire->tableSearch)) {
                    return 'No tasks found';
                } else {
                    return 'No tasks found for "' . $livewire->tableSearch . '"';
                }
            })
            ->query(Task::where('project_id', $this->record->id)->where('status', '!=', 1))
            ->columns([

                TextColumn::make('title')
                    ->label('Title')
                    ->searchable(),

                // ImageColumn::make('users')
                //     ->label('Team')
                //     ->default(function () use ($project) {
                //         $users = $project->users ?? collect();

                //         if ($users->isEmpty()) {
                //             $users = User::whereIn('id', function ($query) use ($project) {
                //                 $query->select('user_id')
                //                     ->from('project_user')
                //                     ->where('project_id', $project->id);
                //             })->get();
                //         }

                //         return $users->map(function ($user) {
                //             return $user->img_avatar
                //                 ?? "https://ui-avatars.com/api/?name=" . urlencode($user->name) . "&background=random";
                //         })->toArray();
                //     })
                //     ->circular()
                //     ->stacked()
                //     ->limit(6)
                //     ->limitedRemainingText()
                //     ->imageHeight(40)
                //     ->extraAttributes([
                //         'style' => 'display: flex;',
                //     ]),

                TextColumn::make('priority')
                    ->label('Priority')
                    ->formatStateUsing(fn($state) => Task::PRIORITY[$state] ?? $state)
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'highest' => 'danger',
                        'high' => 'warning',
                        'medium' => 'primary',
                        'low' => 'info',
                        'lowest' => 'success',
                        default => 'gray',
                    })
                    ->default('N/A'),

                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->state(function ($record) {
                        if (!$record->due_date) {
                            return 'N/A';
                        }

                        $date = Carbon::parse($record->due_date);

                        if ($date->isToday()) {
                            return 'Today';
                        }

                        if ($date->isTomorrow()) {
                            return 'Tomorrow';
                        }

                        return $date->format('d M');
                    }),
            ])
            ->headerActions([
                CreateAction::make('create_task')
                    ->model(Task::class)
                    ->icon('heroicon-s-plus')
                    ->label('New Task')
                    ->modalHeading('Create Task')
                    ->modalWidth('2xl')
                    ->form(self::getTaskForm())
                    ->createAnother(false)
                    ->mutateFormDataUsing(function (array $data): array {
                        if (!isset($data['estimate_time_type'])) {
                            $data['estimate_time_type'] = Task::IN_HOURS;
                        }

                        if ($data['estimate_time_type'] == Task::IN_HOURS) {
                            $data['estimate_time'] = $data['estimate_time'] ?? '00:00';
                        } else {
                            $data['estimate_time'] = is_numeric($data['estimate_time'])
                                ? $data['estimate_time']
                                : '0';
                        }

                        if (empty($data['task_number']) && isset($data['project_id'])) {
                            $data['task_number'] = Task::generateUniqueTaskNumber($data['project_id']);
                        }

                        $data['created_by'] = auth()->id();

                        return $data;
                    })
                    ->after(function (Task $task, array $data) {
                        if (!empty($data['taskAssignee'])) {
                            foreach ($data['taskAssignee'] as $userId) {
                                DB::table('task_assignees')->insert([
                                    'task_id' => $task->id,
                                    'user_id' => $userId,
                                ]);
                            }
                        }

                        if (!empty($data['tags'])) {
                            $task->tags()->sync($data['tags']);
                        }

                        $userIds = $data['taskAssignee'] ?? [];

                        foreach ($userIds as $id) {
                            UserNotification::create([
                                'title'       => 'New Task Assigned',
                                'description' => $task->title . ' assigned to you',
                                'type'        => Task::class,
                                'user_id'     => $id,
                            ]);
                        }

                        $project = $task->project;

                        if ($project) {
                            activity()
                                ->causedBy(getLoggedInUser())
                                ->performedOn($project)
                                ->withProperties([
                                    'model' => Task::class,
                                    'data'  => 'of ' . $project->name,
                                ])
                                ->useLog('Task Created')
                                ->log('Created new task ' . $task->title);
                        }
                    })
                    ->successNotificationTitle('Task Created Successfully'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->iconButton()
                    ->tooltip('View')
                    ->modalHeading('Task Details')
                    ->modalWidth('4xl')
                    ->infolist([
                        Group::make()
                            ->schema([

                                Group::make()
                                    ->schema([

                                        TextEntry::make('title')
                                            ->hiddenLabel()
                                            ->html()
                                            ->extraAttributes(['style' => 'font-size: 1.25rem; font-weight: 600;']),

                                        TextEntry::make('description')
                                            ->label('Description')
                                            ->html()
                                            ->default('No description added yet.'),

                                        Fieldset::make('Attachments')
                                            ->schema([

                                                Action::make('add_attachment')
                                                    ->label('New Attachment')
                                                    ->icon('heroicon-s-plus')
                                                    ->modalHeading('Upload Attachment')
                                                    ->modalWidth('lg')
                                                    ->form([
                                                        SpatieMediaLibraryFileUpload::make('upload_file')
                                                            ->label('Select File')
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

                                                        // Optional: refresh form after upload
                                                        // $this->fillForm();
                                                    }),

                                                Repeater::make('attachments')
                                                    ->label('All Attachments')
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

                                        Fieldset::make('Comments')
                                            ->schema([

                                                Action::make('add_comment')
                                                    ->label('New Comment')
                                                    ->icon('heroicon-s-plus')
                                                    ->modalHeading('Create Comment')
                                                    ->modalWidth('xl')
                                                    ->form([
                                                        RichEditor::make('new_comment')
                                                            ->label('Comment')
                                                            ->required()
                                                            ->columnSpanFull()
                                                            ->placeholder('Add comment...')
                                                            ->extraAttributes(['style' => 'min-height: 200px;'])
                                                            ->toolbarButtons([
                                                                ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link'],
                                                                ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                                                                ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
                                                                ['undo', 'redo'],
                                                            ]),
                                                    ])
                                                    ->action(function (array $data, $record) {
                                                        if ($record && !empty($data['new_comment'])) {
                                                            Comment::create([
                                                                'comment' => $data['new_comment'],
                                                                'task_id' => $record->id,
                                                                'created_by' => auth()->id(),
                                                            ]);
                                                        }
                                                        // $this->fillForm();
                                                    }),

                                                Repeater::make('comment')
                                                    ->label('Comments')
                                                    ->default(function ($record) {
                                                        if (!$record) return [];

                                                        return $record->comments->map(function ($item) {
                                                            return [
                                                                'user_name'  => $item->createdUser->name ?? 'Unknown User',
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
                                            ->label('Assignee')
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


                                        TextEntry::make('task_duration')
                                            ->label('Task Duration')
                                            ->formatStateUsing(function ($record) {

                                                $totalMinutes = $record->time_tracking ?? 0;
                                                $hours = floor($totalMinutes / 60);
                                                $minutes = $totalMinutes % 60;
                                                $seconds = 0;

                                                if ($hours == 0) {
                                                    return sprintf('%02d:%02d m', $minutes, $seconds);
                                                }

                                                return sprintf('%02d:%02d h', $hours, $minutes);
                                            }),

                                        Fieldset::make('Settings')
                                            ->schema([

                                                TextEntry::make('created_at')
                                                    ->label('Start At')
                                                    ->inlineLabel()
                                                    ->formatStateUsing(function ($state) {
                                                        return Carbon::parse($state)->format('jS M, Y');
                                                    }),

                                                TextEntry::make('due_date')
                                                    ->label('Due Date')
                                                    ->inlineLabel()
                                                    ->formatStateUsing(function ($state) {
                                                        if (!$state) {
                                                            return 'N/A';
                                                        }

                                                        $date = Carbon::parse($state);
                                                        return $date->format('jS M, Y');
                                                    }),

                                                TextEntry::make('status')
                                                    ->label('Status')
                                                    ->inlineLabel()
                                                    ->formatStateUsing(fn($state) => Status::where('status', $state)->value('name') ?? $state),

                                                TextEntry::make('priority')
                                                    ->label('Priority')
                                                    ->inlineLabel()
                                                    ->default('N/A')
                                                    ->formatStateUsing(fn($state) => Task::PRIORITY[$state] ?? $state),

                                            ])
                                            ->columns(1),

                                        Fieldset::make('Information')
                                            ->schema([

                                                TextEntry::make('created_by')
                                                    ->label('Created By')
                                                    ->inlineLabel()
                                                    ->formatStateUsing(function ($state) {
                                                        return User::find($state)->name;
                                                    }),

                                                TextEntry::make('created_at')
                                                    ->label('Created On')
                                                    ->inlineLabel()
                                                    ->formatStateUsing(function ($state) {
                                                        return Carbon::parse($state)->format('jS M, Y');
                                                    }),

                                                TextEntry::make('time_tracking')
                                                    ->label('Time Tracking')
                                                    ->inlineLabel()
                                                    ->formatStateUsing(function ($record) {
                                                        $totalMinutes = $record->time_tracking ?? 0;
                                                        $hours = floor($totalMinutes / 60);
                                                        $minutes = $totalMinutes % 60;
                                                        $seconds = 0;

                                                        if ($hours == 0) {
                                                            return sprintf('%02d:%02d m', $minutes, $seconds);
                                                        }

                                                        return sprintf('%02d:%02d h', $hours, $minutes);
                                                    })
                                                    ->default('00:00 m'),

                                                TextEntry::make('project.name')
                                                    ->label('Project')
                                                    ->inlineLabel(),


                                            ])
                                            ->columns(1),

                                    ])
                                    ->columnSpan(1),

                            ])
                            ->columnSpanFull()
                            ->columns(3),
                    ]),

                EditAction::make('edit_task')
                    ->model(Task::class)
                    ->iconButton()
                    ->tooltip('Edit')
                    ->modalHeading('Edit Task')
                    ->modalWidth('2xl')
                    ->form(self::getTaskForm())
                    ->mutateFormDataUsing(function (array $data): array {

                        if (!isset($data['estimate_time_type'])) {
                            $data['estimate_time_type'] = Task::IN_HOURS;
                        }

                        if ($data['estimate_time_type'] == Task::IN_HOURS) {
                            $data['estimate_time'] = $data['estimate_time'] ?? '00:00';
                        } else {
                            $data['estimate_time'] = is_numeric($data['estimate_time'])
                                ? $data['estimate_time']
                                : '0';
                        }

                        if (empty($data['task_number']) && isset($data['project_id'])) {
                            $data['task_number'] = Task::generateUniqueTaskNumber($data['project_id']);
                        }

                        $data['updated_by'] = auth()->id();

                        return $data;
                    })
                    ->after(function (Task $task, array $data) {

                        if (!empty($data['taskAssignee'])) {
                            DB::table('task_assignees')
                                ->where('task_id', $task->id)
                                ->delete();

                            foreach ($data['taskAssignee'] as $userId) {
                                DB::table('task_assignees')->insert([
                                    'task_id' => $task->id,
                                    'user_id' => $userId,
                                ]);
                            }
                        }

                        if (!empty($data['tags'])) {
                            $task->tags()->sync($data['tags']);
                        }
                    })
                    ->successNotificationTitle('Task Updated Successfully')
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public static function getTaskForm(): array
    {
        return [
            Group::make()
                ->schema([
                    TextInput::make('title')
                        ->label('Title')
                        ->placeholder('Title')
                        ->required(),

                    Hidden::make('project_id')
                        ->default(function ($livewire) {
                            return $livewire->record?->id;
                        }),

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
                            })->pluck('name', 'id');
                        })
                        ->preload()
                        ->searchable()
                        ->native(false)
                        ->required(fn(callable $get) => !empty($get('project_id')))
                        ->afterStateHydrated(function (callable $set, $record) {
                            if ($record) {
                                $set(
                                    'taskAssignee',
                                    $record->taskAssignee->pluck('id')->toArray()

                                );
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
                        ->suffixAction(
                            Action::make('set_hours')
                                ->button()
                                ->label('In Hours')
                                ->size('xs')
                                ->color(fn($get) => $get('estimate_time_type') === Task::IN_HOURS ? 'primary' : 'secondary')
                                ->action(function ($set) {
                                    $set('estimate_time_type', Task::IN_HOURS);
                                    $set('estimate_time', null);
                                })
                        )
                        ->suffixAction(
                            Action::make('set_days')
                                ->button()
                                ->label('In Days')
                                ->size('xs')
                                ->color(fn($get) => $get('estimate_time_type') === Task::IN_DAYS ? 'primary' : 'secondary')
                                ->action(function ($set) {
                                    $set('estimate_time_type', Task::IN_DAYS);
                                    $set('estimate_time', null);
                                })
                        ),

                    Select::make('tags')
                        ->label('Tags')
                        ->multiple()
                        ->relationship('tags', 'name')
                        ->preload()
                        ->searchable()
                        ->native(false),

                    Select::make('status')
                        ->label('Status')
                        ->options(Status::all()->pluck('name', 'status'))
                        ->searchable()
                        ->visible(function (?string $operation) {
                            return $operation === 'edit_task';
                        }),

                    RichEditor::make('description')
                        ->label('Description')
                        ->placeholder('Description')
                        ->columnSpanFull()
                        ->extraAttributes(['style' => 'min-height: 200px;'])
                        ->toolbarButtons([
                            ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link'],
                            ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                            ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
                            ['undo', 'redo'],
                        ]),
                ])->columns(2),
        ];
    }
}
