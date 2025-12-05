<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\UserNotification;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\ImageEntry;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;

class ListProjects extends Page implements HasForms
{
    use WithPagination, InteractsWithForms;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.projects.pages.list-projects';

    public int $perPage = 12;

    public ?string $search = '';

    public int | null $status = Project::STATUS_ONGOING;
    public int | null $client_id = null;

    protected array $perPageOptions = [5, 10, 20, 50, 100, 'all'];

    public function getTitle(): string
    {
        return __('messages.projects.projects');
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('search')
                ->hiddenLabel()
                ->placeholder(__('messages.projects.search_projects'))
                ->live(debounce: 500)
                ->afterStateUpdated(function ($state) {
                    $this->search = $state;
                    $this->resetPage();
                }),

            Select::make('status')
                ->hiddenLabel()
                ->options(Project::STATUS)
                ->placeholder(__('messages.projects.all_statuses'))
                ->native(false)
                ->extraAttributes([
                    'style' => 'min-width: 180px'
                ])
                ->live()
                ->afterStateUpdated(function ($state) {
                    $this->status = $state;
                    $this->resetPage();
                }),

            Select::make('client_id')
                ->hiddenLabel()
                ->options(Client::pluck('name', 'id'))
                ->placeholder(__('messages.projects.all_clients'))
                ->native(false)
                ->searchable()
                ->extraAttributes([
                    'style' => 'min-width: 250px'
                ])
                ->live()
                ->afterStateUpdated(function ($state) {
                    $this->client_id = $state;
                    $this->resetPage();
                }),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-s-plus')
                ->label(__('messages.projects.new_project'))
                ->modalWidth('2xl')
                ->createAnother(false)
                ->successNotificationTitle(__('messages.projects.project_created_successfully'))
                ->form(fn($form) => ProjectResource::form($form))
                ->after(function ($record, array $data) {
                    $userIds = $data['users'] ?? $record->users()->pluck('users.id')->toArray();

                    foreach ($userIds as $id) {
                        UserNotification::create([
                            'title'       => 'New Project Assigned',
                            'description' => $record->name . ' assigned to you',
                            'type'        => Project::class,
                            'user_id'     => $id,
                        ]);
                    }

                    activity()
                        ->causedBy(getLoggedInUser())
                        ->performedOn($record)
                        ->withProperties([
                            'model' => Project::class,
                            'data'  => $record->name,
                        ])
                        ->useLog('Project Created')
                        ->log('Created project');

                    activity()
                        ->causedBy(getLoggedInUser())
                        ->performedOn($record)
                        ->withProperties([
                            'model' => Project::class,
                            'data'  => '',
                        ])
                        ->useLog('Project Assign To User')
                        ->log('Assigned ' . $record->name . ' to ' . implode(',', $record->users()->pluck('name')->toArray()));
                }),
        ];
    }

    public function getPerPageOptions(): array
    {
        return $this->perPageOptions;
    }

    public function getProjectsProperty()
    {
        return $this->getProjects($this->perPage);
    }

    public function getProjects($perPage = 12)
    {
        $query = Project::with(['client', 'users'])->latest();

        if (!empty($this->search)) {
            $query->where(function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('prefix', 'like', '%' . $this->search . '%');
            });
        }

        if (!is_null($this->status) && $this->status > 0) {
            $query->where('status', $this->status);
        }

        if (!is_null($this->client_id)) {
            $query->where('client_id', $this->client_id);
        }

        if ($perPage === 'all') {
            $projects = $query->get();
            return new LengthAwarePaginator(
                $projects,
                $projects->count(),
                $projects->count(),
                1
            );
        }

        return $query->paginate($perPage);
    }

    public function projectsInfolist(Schema $schema): Schema
    {
        $projects = $this->getProjectsProperty();
        return $schema
            ->components(function () use ($projects) {
                if (!empty($projects)) {
                    $projectsData = [];
                    foreach ($projects as $project) {
                        /** @var Project $project */
                        $projectsData[] = Section::make()
                            ->schema([
                                Group::make([
                                    Group::make([

                                        ImageEntry::make('image')
                                            ->circular()
                                            ->hiddenLabel()
                                            ->width(45)
                                            ->height(45)
                                            ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . urlencode($project->name) . '&background=random')
                                            ->columnSpan(1),

                                        TextEntry::make('name')
                                            ->hiddenLabel()
                                            ->html()
                                            ->url(ProjectResource::getUrl('view', ['record' => $project->id]))
                                            ->default(function () use ($project) {
                                                $prefix = $project->prefix ?? '';
                                                $name = $project->name ?? '-';

                                                $formatted = $prefix
                                                    ? "<strong style='font-size: 16px;'>{$name}</strong> <br> <span style='font-size: 12px;'>({$prefix})</span>"
                                                    : "<strong style='font-size: 12px;'>{$name}</strong>";

                                                return $formatted;
                                            })
                                            ->columnSpan(6),

                                    ])
                                        ->columns(7)
                                        ->columnSpan(4),

                                    ActionGroup::make([

                                        Action::make('view' . $project->id)
                                            ->label(__('messages.common.view'))
                                            ->icon('heroicon-s-eye')
                                            ->url(fn() => ProjectResource::getUrl('view', ['record' => $project->id])),

                                        self::getEditForm($project),

                                        self::getDeleteAction($project),
                                    ])
                                        ->extraAttributes(['style' => 'margin-left: auto;'])
                                ])
                                    ->columns(5),

                                Group::make([
                                    Group::make([

                                        TextEntry::make('created_at')
                                            ->hiddenLabel()
                                            ->default(function () use ($project) {

                                                $progress = $project->projectProgress();
                                                $progressFormatted = number_format($progress, 2);

                                                return "{$progressFormatted}%";
                                            }),

                                        TextEntry::make('created_at')
                                            ->hiddenLabel()
                                            ->default(function () use ($project) {
                                                $total = $project->tasks()->count();
                                                $pending = $project->tasks()->where('status', Task::STATUS_PENDING)->count();

                                                return "{$pending}/{$total}";
                                            })
                                            ->extraAttributes([
                                                'style' => 'display: flex; justify-content: flex-end;',
                                            ]),

                                    ])
                                        ->columns(2)
                                        ->columnSpan(4),
                                ])
                                    ->columns(5),

                                Group::make([

                                    TextEntry::make('progress')
                                        ->hiddenLabel()
                                        ->columnSpanFull()
                                        ->html()
                                        ->default(function () use ($project) {
                                            $progress = $project->projectProgress();
                                            $color = $project->color ?? '#3b82f6';
                                            $background = '#e5e7eb';

                                            return <<<HTML
                                                    <div style="position: relative; width: 100%; height: 8px; background-color: {$background}; border-radius: 10px; overflow: hidden;">
                                                        <div style="position: absolute; left: 0; top: 0; height: 100%; width: {$progress}%; background-color: {$color}; transition: width 0.3s ease;"></div>
                                                    </div>
                                                HTML;
                                        })
                                        ->columnSpan(4),

                                    TextEntry::make('status')
                                        ->hiddenLabel()
                                        ->badge()
                                        ->default(function () use ($project) {
                                            $statuses = [
                                                0 => __('messages.status.all'),
                                                1 => __('messages.status.ongoing'),
                                                2 => __('messages.status.finished'),
                                                3 => __('messages.status.on_hold'),
                                                4 => __('messages.status.archived'),
                                            ];

                                            $statusValue = $project->status ?? null;

                                            return $statuses[$statusValue] ?? '-';
                                        })
                                        ->color(function () use ($project) {
                                            $colors = [
                                                0 => 'gray',
                                                1 => 'info',
                                                2 => 'success',
                                                3 => 'warning',
                                                4 => 'danger',
                                            ];

                                            return $colors[$project->status] ?? 'gray';
                                        })->extraAttributes(['style' => 'margin-top: -15px;'])
                                        ->columnSpan(1),

                                ])
                                    ->columns(5),

                                Group::make([

                                    ImageEntry::make('users')
                                        ->label('Team')
                                        ->hiddenLabel()
                                        ->default(function () use ($project) {
                                            $users = $project->users ?? collect();

                                            if ($users->isEmpty()) {
                                                $users = User::whereIn('id', function ($query) use ($project) {
                                                    $query->select('user_id')
                                                        ->from('project_user')
                                                        ->where('project_id', $project->id);
                                                })->get();
                                            }
                                            return $users->map(function ($user) {
                                                return $user->img_avatar
                                                    ?? "https://ui-avatars.com/api/?name=" . urlencode($user->name) . "&background=random";
                                            })->toArray();
                                        })
                                        ->circular()
                                        ->stacked()
                                        ->limit(5)
                                        ->limitedRemainingText()
                                        ->imageHeight(40)
                                        ->extraAttributes([
                                            'style' => 'display: flex;',
                                        ]),

                                    Action::make('View' . $project->id)
                                        ->label('View')
                                        ->iconButton()
                                        ->icon('heroicon-s-plus')
                                        ->color('info')
                                        ->modalHeading(__('messages.projects.edit_assignees'))
                                        ->modalWidth('md')
                                        ->fillForm(fn() => [
                                            'users' => $project->users->pluck('id')->toArray(),
                                        ])
                                        ->form([
                                            Select::make('users')
                                                ->label(__('messages.users.users'))
                                                ->multiple()
                                                ->preload()
                                                ->searchable()
                                                ->required()
                                                ->options(
                                                    User::where('is_active', true)->pluck('name', 'id')
                                                )
                                                ->columnSpanFull(),
                                        ])
                                        ->action(function (array $data) use ($project) {

                                            $oldUserIds = $project->users()->pluck('users.id')->toArray();
                                            $project->users()->sync($data['users']);

                                            $newUserIds = array_diff($data['users'], $oldUserIds);
                                            $removedUserIds = array_diff($oldUserIds, $data['users']);

                                            foreach ($removedUserIds as $removedUserId) {
                                                UserNotification::create([
                                                    'title'       => 'Removed From Project',
                                                    'description' => 'You were removed from ' . $project->name,
                                                    'type'        => Project::class,
                                                    'user_id'     => $removedUserId,
                                                ]);
                                            }

                                            foreach ($newUserIds as $newUserId) {
                                                $newUser = User::find($newUserId);
                                                if ($newUser) {
                                                    foreach ($newUserIds as $newUserId) {
                                                        UserNotification::create([
                                                            'title'       => 'New Project Assigned',
                                                            'description' => $project->name . ' assigned to you',
                                                            'type'        => Project::class,
                                                            'user_id'     => $newUserId,
                                                        ]);
                                                    }
                                                    foreach ($oldUserIds as $oldUserId) {
                                                        UserNotification::create([
                                                            'title'       => 'New User Assigned to Project',
                                                            'description' => $newUser->name . ' assigned to ' . $project->name,
                                                            'type'        => Project::class,
                                                            'user_id'     => $oldUserId,
                                                        ]);
                                                    }
                                                }
                                            }

                                            activity()
                                                ->causedBy(getLoggedInUser())
                                                ->performedOn($project)
                                                ->withProperties([
                                                    'model' => Project::class,
                                                    'data'  => '',
                                                ])
                                                ->useLog('Project Assignee Updated')
                                                ->log('Assigned ' . $project->name . ' to ' . implode(', ', $project->users()->pluck('name')->toArray()));
                                        })
                                        ->after(fn() => redirect(request()->header('Referer')))
                                        ->successNotificationTitle(__('messages.projects.assignee_updated_successfully'))
                                        ->extraAttributes([
                                            'style' => 'display: flex; justify-content: center; align-items: center; border: 1px solid #5689fd; border-radius: 50%; padding: 0.5rem; margin: 2px 0px 2px -17px;',
                                        ])

                                ])
                                    ->columns([
                                        'default' => 2,
                                        'sm' => 2,
                                        'xs' => 1,
                                    ])
                                    ->extraAttributes([
                                        'style' => 'display: flex; flex-wrap: wrap; gap: 10px; align-items: center; ',
                                    ])
                            ]);
                    }
                    return Group::make($projectsData)->columns(3);
                }
            });
    }

    public function updatedPerPage($value): void
    {
        $this->resetPage();
    }

    public function getStatusLabel(): string
    {
        if (is_null($this->status)) {
            return '';
        }

        $statuses = Project::STATUS;
        return $statuses[$this->status] ?? '';
    }

    public function getClientName(): string
    {
        if (is_null($this->client_id)) {
            return '';
        }

        $client = Client::find($this->client_id);
        return $client ? $client->name : '';
    }

    public static function getEditForm(Project $project): Action
    {
        return Action::make('Edit' . $project->id)
            ->label(__('messages.common.edit'))
            ->icon('heroicon-s-pencil')
            ->modalHeading(__('messages.projects.edit_project'))
            ->modalWidth('3xl')
            ->color('info')
            ->fillForm(function () use ($project) {
                return [
                    'name' => $project->name,
                    'prefix' => $project->prefix,
                    'client_id' => $project->client_id,
                    'price' => $project->price,
                    'budget_type' => $project->budget_type,
                    'currency' => $project->currency,
                    'status' => $project->status,
                    'color' => $project->color,
                    'description' => $project->description,
                    'user_ids' => $project->users()->pluck('users.id')->toArray(),
                ];
            })
            ->form([
                Group::make([

                    TextInput::make('name')
                        ->label(__('messages.common.name'))
                        ->placeholder(__('messages.common.name'))
                        ->live()
                        ->unique()
                        ->required()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if (! $state) {
                                $set('prefix', null);
                                return;
                            }
                            $prefix = strtoupper(str_replace(' ', '', $state));
                            $prefix = substr($prefix, 0, 8);

                            $set('prefix', $prefix);
                        }),

                    TextInput::make('prefix')
                        ->label(__('messages.projects.prefix'))
                        ->placeholder(__('messages.projects.prefix'))
                        ->maxLength(8)
                        ->reactive()
                        ->unique()
                        ->required(),

                    Select::make('user_ids')
                        ->label(__('messages.users.users'))
                        ->multiple()
                        ->options(User::pluck('name', 'id'))
                        ->searchable()
                        ->preload(),


                    Select::make('client_id')
                        ->label(__('messages.users.client'))
                        ->options(Client::all()->pluck('name', 'id'))
                        ->preload()
                        ->searchable()
                        ->required(),

                    TextInput::make('price')
                        ->numeric()
                        ->placeholder(__('messages.projects.budget'))
                        ->label(__('messages.projects.budget'))
                        ->required(),

                    Select::make('budget_type')
                        ->label(__('messages.projects.budget_type'))
                        ->options(Project::BUDGET_TYPE)
                        ->native(false)
                        ->hintIcon(Heroicon::QuestionMarkCircle, tooltip: __('messages.projects.budget_type_tooltip'))
                        ->required(),

                    Group::make([

                        Select::make('currency')
                            ->label(__('messages.projects.currency'))
                            ->options(function () {
                                return collect(Project::CURRENCY)->mapWithKeys(function ($name, $key) {
                                    return [$key => Project::getCurrencyClass($key) . ' ' . $name];
                                })->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->required(),

                        Select::make('status')
                            ->label(__('messages.common.status'))
                            ->options(Project::STATUS)
                            ->native(false)
                            ->required(),

                        ColorPicker::make('color')
                            ->label(__('messages.common.color'))
                            ->placeholder(__('messages.common.color')),

                    ])
                        ->columnSpanFull()
                        ->columns(3),

                    RichEditor::make('description')
                        ->label(__('messages.common.description'))
                        ->placeholder(__('messages.common.description'))
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
            ])
            ->action(function (array $data) use ($project): void {

                $oldStatus = $project->status;
                $newStatus = $data['status'] ?? $project->status;

                $oldUserIds = $project->users()->pluck('users.id')->toArray();

                $project->update([
                    'name' => $data['name'],
                    'prefix' => $data['prefix'],
                    'client_id' => $data['client_id'],
                    'price' => $data['price'],
                    'budget_type' => $data['budget_type'],
                    'currency' => $data['currency'],
                    'status' => $data['status'],
                    'color' => $data['color'],
                    'description' => $data['description'],
                ]);

                if (isset($data['user_ids'])) {
                    $project->users()->sync($data['user_ids']);
                }

                $newUserIds = array_diff($data['user_ids'], $oldUserIds);
                $removedUserIds = array_diff($oldUserIds, $data['user_ids']);

                foreach ($newUserIds as $newUserId) {
                    $newUser = User::find($newUserId);
                    if ($newUser) {
                        UserNotification::create([
                            'title'       => 'New Project Assigned',
                            'description' => $project->name . ' assigned to you',
                            'type'        => Project::class,
                            'user_id'     => $newUser->id,
                        ]);

                        foreach (array_diff($oldUserIds, $removedUserIds) as $existingUserId) {
                            UserNotification::create([
                                'title'       => 'New User Assigned to Project',
                                'description' => $newUser->name . ' assigned to ' . $project->name,
                                'type'        => Project::class,
                                'user_id'     => $existingUserId,
                            ]);
                        }
                    }
                }

                foreach ($removedUserIds as $removedUserId) {
                    UserNotification::create([
                        'title'       => 'Removed From Project',
                        'description' => 'You removed from ' . $project->name,
                        'type'        => Project::class,
                        'user_id'     => $removedUserId,
                    ]);
                }

                if ($oldStatus != $newStatus) {
                    $oldStatusText = Project::STATUS[$oldStatus] ?? $oldStatus;
                    $newStatusText = Project::STATUS[$newStatus] ?? $newStatus;

                    $projectUsers = $project->users()->pluck('users.id')->toArray();
                    foreach ($projectUsers as $userId) {
                        UserNotification::create([
                            'title'       => 'Project Status Changed',
                            'description' => 'Project status changed from ' . $oldStatusText . ' to ' . $newStatusText,
                            'type'        => Project::class,
                            'user_id'     => $userId,
                        ]);
                    }

                    if (!empty($project->client->user_id)) {
                        UserNotification::create([
                            'title'       => 'Project Status Changed',
                            'description' => 'Project status changed from ' . $oldStatusText . ' to ' . $newStatusText,
                            'type'        => Project::class,
                            'user_id'     => $project->client->user_id,
                        ]);
                    }
                }

                activity()
                    ->causedBy(getLoggedInUser())
                    ->performedOn($project)
                    ->withProperties([
                        'model' => Project::class,
                        'data'  => $project->name,
                    ])
                    ->useLog('Project Updated')
                    ->log('Updated Project');
            })
            ->successNotificationTitle(__('messages.projects.project_updated_successfully'));
    }

    public static function getDeleteAction(Project $project): Action
    {
        return Action::make('Delete' . $project->id)
            ->label(__('messages.common.delete'))
            ->icon('heroicon-s-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->action(function () use ($project) {
                $project->delete();
            })
            ->after(function ($livewire) {
                $livewire->dispatch('refresh');
            })
            ->successNotificationTitle(__('messages.projects.project_deleted_successfully'));
    }
}
