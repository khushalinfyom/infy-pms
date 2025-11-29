<?php

namespace App\Filament\Client\Resources\Projects\Pages;

use App\Filament\Client\Resources\Projects\ProjectResource;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
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
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;

class ListProjects extends Page implements HasForms
{
    use WithPagination, InteractsWithForms;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.client.resources.projects.pages.list-projects';

    public int $perPage = 12;

    public string $search = '';

    public int | null $status = Project::STATUS_ONGOING;
    public int | null $client_id = null;

    protected array $perPageOptions = [5, 10, 20, 50, 100, 'all'];

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('search')
                ->hiddenLabel()
                ->placeholder('Search projects...')
                ->live(debounce: 500)
                ->afterStateUpdated(function ($state) {
                    $this->search = $state;
                    $this->resetPage();
                }),

            Select::make('status')
                ->hiddenLabel()
                ->options(Project::STATUS)
                ->placeholder('All Statuses')
                ->native(false)
                ->extraAttributes([
                    'style' => 'min-width: 180px'
                ])
                ->live()
                ->afterStateUpdated(function ($state) {
                    $this->status = $state;
                    $this->resetPage();
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

        if (auth()->user()->hasRole('Client')) {
            $ownerId = auth()->user()->owner_id;
            $query->where('client_id', $ownerId);
        }

        if (!empty($this->search)) {
            $query->where(function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('prefix', 'like', '%' . $this->search . '%');
            });
        }

        if (!is_null($this->status) && $this->status > 0) {
            $query->where('status', $this->status);
        }

        if (!auth()->user()->hasRole('Client') && !is_null($this->client_id)) {
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
                                                0 => 'All',
                                                1 => 'Ongoing',
                                                2 => 'Finished',
                                                3 => 'On Hold',
                                                4 => 'Archived',
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
}
