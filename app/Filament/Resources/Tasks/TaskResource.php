<?php

namespace App\Filament\Resources\Tasks;

use App\Enums\AdminPanelSidebar;
use App\Filament\Resources\Tasks\Pages\ManageTasks;
use App\Filament\Resources\Tasks\Pages\TaskDetails;
use App\Filament\Resources\Tasks\Widgets\TaskAttachmentTable;
use App\Filament\Resources\Tasks\Widgets\TaskCommentsTable;
use App\Filament\Resources\Tasks\Widgets\TaskTimeEntryTable;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Filters\SelectFilter;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static ?int $navigationSort = AdminPanelSidebar::TASKS->value;

    protected static ?string $recordTitleAttribute = 'Task';

    public static function canViewAny(): bool
    {
        return authUserHasPermission('manage_all_tasks');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('title')
                    ->label('Title')
                    ->placeholder('Title')
                    ->required(),

                Select::make('project_id')
                    ->label('Project')
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

                // TextInput::make('estimate_time')
                //     ->label('Estimate Time')
                //     ->reactive()
                //     ->placeholder('Enter estimate')
                //     ->default(0)
                //     ->afterStateHydrated(function ($set, $get) {
                //         if (! $get('estimate_time_type')) {
                //             $set('estimate_time_type', Task::IN_HOURS);
                //         }
                //     })
                //     ->extraInputAttributes(function ($get) {
                //         return [
                //             'type' => $get('estimate_time_type') === Task::IN_HOURS ? 'time' : 'number',
                //             'min' => 0,
                //         ];
                //     })
                //     ->suffixAction(
                //         Action::make('set_hours')
                //             ->button()
                //             ->label('In Hours')
                //             ->size('xs')
                //             ->color(fn($get) => $get('estimate_time_type') === Task::IN_HOURS ? 'primary' : 'secondary')
                //             ->action(function ($set) {
                //                 $set('estimate_time_type', Task::IN_HOURS);
                //                 $set('estimate_time', null);
                //             })
                //     )
                //     ->suffixAction(
                //         Action::make('set_days')
                //             ->button()
                //             ->label('In Days')
                //             ->size('xs')
                //             ->color(fn($get) => $get('estimate_time_type') === Task::IN_DAYS ? 'primary' : 'secondary')
                //             ->action(function ($set) {
                //                 $set('estimate_time_type', Task::IN_DAYS);
                //                 $set('estimate_time', null);
                //             })
                //     ),

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
                    ->relationship('tags', 'name')
                    ->preload()
                    ->searchable()
                    ->native(false),

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

            ])
            ->columns(2);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->contained(false)
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('taskdetail')
                            ->label('Task Details')
                            ->schema([
                                Section::make()
                                    ->schema([

                                        TextEntry::make('title')
                                            ->hiddenLabel()
                                            ->columnSpanFull(),

                                        TextEntry::make('assignees')
                                            ->label('Assignee')
                                            ->getStateUsing(function ($record) {
                                                return $record->taskAssignee->pluck('name')->implode(', ');
                                            }),

                                        TextEntry::make('due_date')
                                            ->label('Due Date')
                                            ->formatStateUsing(fn($state) => Carbon::parse($state)->format('jS F, Y'))
                                            ->visible(fn($record) => $record->due_date),

                                        TextEntry::make('status')
                                            ->label('Status')
                                            ->getStateUsing(fn($record) => $record->getStatusTextAttribute()),

                                        TextEntry::make('duration')
                                            ->label('Time Tracking')
                                            ->getStateUsing(function ($record) {
                                                $totalMinutes = $record->timeEntries->sum('duration');
                                                $hours = floor($totalMinutes / 60);
                                                $minutes = $totalMinutes % 60;
                                                return sprintf('%02d:%02d M', $hours, $minutes);
                                            }),

                                        TextEntry::make('created_by')
                                            ->label('Reporter')
                                            ->getStateUsing(function ($record) {
                                                return $record->createdUser->name;
                                            }),

                                        TextEntry::make('created_at')
                                            ->label('Created On')
                                            ->getStateUsing(fn($record) => Carbon::parse($record->created_at)->diffForHumans()),

                                        TextEntry::make('updated_at')
                                            ->label('Updated On')
                                            ->getStateUsing(fn($record) => Carbon::parse($record->updated_at)->diffForHumans()),

                                        TextEntry::make('description')
                                            ->label('Description')
                                            ->html()
                                            ->columnSpanFull()
                                    ])
                                    ->columns(3),
                            ]),

                        Tab::make('attachments')
                            ->label('Attachments')
                            ->schema([
                                Livewire::make(TaskAttachmentTable::class)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('comments')
                            ->label('Comments')
                            ->schema([
                                Livewire::make(TaskCommentsTable::class)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('taskTimeEntry')
                            ->label('Task Time Entries')
                            ->schema([
                                Livewire::make(TaskTimeEntryTable::class)
                                    ->columnSpanFull(),
                            ])
                    ])
                    ->columnSpan(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordAction(null)
            ->paginated([10, 25, 50, 100])
            ->defaultSort('id', 'desc')
            ->recordActionsColumnLabel('Action')
            ->emptyStateHeading(function ($livewire) {
                if (empty($livewire->tableSearch)) {
                    return 'No tasks found.';
                } else {
                    return 'No tasks found for "' . $livewire->tableSearch . '".';
                }
            })
            ->query(function () {
                return Task::whereHas('taskAssignee', function ($q) {
                    $q->where('user_id', auth()->id());
                })
                    ->where('status', '!=', Task::STATUS_COMPLETED);
            })
            ->recordTitleAttribute('Task')
            ->columns([

                TextColumn::make('title')
                    ->label('Title')
                    ->searchable(),

                TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable(),

                ImageColumn::make('users')
                    ->label('Assignees')
                    ->circular()
                    ->stacked()
                    ->limit(6)
                    ->limitedRemainingText()
                    ->imageHeight(40)
                    ->getStateUsing(function ($record) {
                        return $record->taskAssignee->map(function ($user) {
                            $avatar = $user->getFirstMediaUrl(User::IMAGE_PATH);

                            return $avatar ?: 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=random';
                        })->toArray();
                    }),
            ])
            ->filters([
                SelectFilter::make('project_id')
                    ->label('Project')
                    ->relationship('project', 'name')
                    ->native(false)
                    ->searchable()
                    ->preload(),
            ])
            ->deferFilters(false)
            ->recordActions([

                Action::make('completed')
                    ->tooltip('Mark as Completed')
                    ->icon('heroicon-s-check-circle')
                    ->color('success')
                    ->iconButton()
                    ->action(function ($record) {
                        $record->update([
                            'status' => Task::STATUS_COMPLETED,
                        ]);
                    })
                    ->visible(function ($record) {
                        return $record->status != Task::STATUS_COMPLETED;
                    })
                    ->successNotificationTitle('Task marked as completed successfully!'),

                Action::make('view')
                    ->tooltip('View')
                    ->icon('heroicon-s-eye')
                    ->iconButton()
                    ->modalWidth('4xl')
                    ->modalHeading('Task Details')
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
                                            ->placeholder('No description added yet.'),

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

                                        TextEntry::make('duration')
                                            ->label('Time Tracking')
                                            ->getStateUsing(function ($record) {
                                                $totalMinutes = $record->timeEntries->sum('duration');
                                                $hours = floor($totalMinutes / 60);
                                                $minutes = $totalMinutes % 60;

                                                $time = sprintf('%02d:%02d M', $hours, $minutes);

                                                return "<div class='text-center font-bold text-xl'>{$time}</div>";
                                            })
                                            ->formatStateUsing(fn($state) => $state)
                                            ->html(),

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
                                                    })
                                                    ->placeholder('N/A'),

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
                                                    ->getStateUsing(function ($record) {
                                                        $totalMinutes = $record->timeEntries->sum('duration');
                                                        $hours = floor($totalMinutes / 60);
                                                        $minutes = $totalMinutes % 60;
                                                        return sprintf('%02d:%02d M', $hours, $minutes);
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

                Action::make('due date')
                    ->iconButton()
                    ->tooltip('Set Due Date')
                    ->icon('heroicon-s-calendar')
                    ->modalWidth('md')
                    ->modalHeading('Add Due Date')
                    ->form([
                        DatePicker::make('due_date')
                            ->label('Due Date')
                            ->required()
                            ->native(false)
                            ->placeholder('Due Date')
                            ->minDate(Carbon::now()->subDays(1)),
                    ])
                    ->action(function ($record, $data) {
                        $record->update($data);
                    })
                    ->visible(function ($record) {
                        return $record->due_date == null;
                    })
                    ->successNotificationTitle('Due Date added successfully!'),

                ActionGroup::make([

                    EditAction::make()
                        ->modalWidth('4xl')
                        ->modalHeading('Edit Task')
                        ->successNotificationTitle('Task updated successfully!')
                        ->after(function ($record, array $data) {
                            if (isset($data['taskAssignee'])) {
                                $record->taskAssignee()->sync($data['taskAssignee']);
                            }
                        }),

                    Action::make('taskEntry')
                        ->label('New Time Entry')
                        ->tooltip('New Time Entry')
                        ->icon('heroicon-o-clock')
                        ->modalWidth('2xl')
                        ->modalHeading('Create Time Entry')
                        ->form(fn($record) => self::createTimeEntryForm($record))
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
                        ->successNotificationTitle('Time Entry added successfully!'),

                    DeleteAction::make()
                        ->tooltip('Delete')
                        ->modalHeading('Delete Task')
                        ->successNotificationTitle('Task deleted successfully!'),
                ]),

                Action::make('details')
                    ->iconButton()
                    ->tooltip('Details')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->url(fn($record) => TaskResource::getUrl('taskdetails', ['record' => $record->id])),

            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTasks::route('/'),
            'task-kanban' => Pages\TaskKanban::route('/kanban'),
            'taskdetails' => TaskDetails::route('/{record}'),
        ];
    }

    public static function createTimeEntryForm($record)
    {
        return [

            Hidden::make('entry_type')
                ->default(TimeEntry::VIA_FORM),

            Hidden::make('task_id')
                ->default($record->id),

            Hidden::make('user_id')
                ->default(auth()->user()->id),

            Select::make('user_id')
                ->label('User')
                ->relationship('createdUser', 'name')
                ->required()
                ->native(false)
                ->default(auth()->user()->id)
                ->disabled()
                ->columnSpanFull(),

            Group::make([
                Group::make([

                    Select::make('project_id')
                        ->label('Project')
                        ->relationship('project', 'name')
                        ->required()
                        ->native(false)
                        ->default($record->project_id)
                        ->disabled(),

                    DateTimePicker::make('start_time')
                        ->label('Start Time')
                        ->placeholder('Start Time')
                        ->required()
                        ->native(false)
                        ->maxDate(now())
                        ->live()
                        ->afterStateUpdated(
                            fn($state, callable $set, callable $get) =>
                            self::updateDuration($get, $set)
                        ),

                    DateTimePicker::make('end_time')
                        ->label('End Time')
                        ->placeholder('End Time')
                        ->required()
                        ->native(false)
                        ->maxDate(now())
                        ->minDate($record->start_time)
                        ->live()
                        ->default(now())
                        ->afterStateUpdated(
                            fn($state, callable $set, callable $get) =>
                            self::updateDuration($get, $set)
                        ),

                    TextInput::make('duration')
                        ->label('Duration (In Minutes)')
                        ->placeholder('Duration')
                        ->disabled()
                        ->required()
                        ->live(),

                ])
                    ->columns(1),

                Group::make([

                    Select::make('task_id')
                        ->label('Task')
                        ->required()
                        ->native(false)
                        ->options([
                            $record->id => $record->title
                        ])
                        ->default($record->id)
                        ->disabled(),

                    Select::make('activity_type_id')
                        ->label('Activity Type')
                        ->relationship('timeEntries.activityType', 'name')
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

    public static function updateDuration(callable $get, callable $set)
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
