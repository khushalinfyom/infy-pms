<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Livewire\WithPagination;

class ListProjects extends Page
{
    use WithPagination;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.projects.pages.list-projects';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-s-plus')
                ->label('New Project')
                ->modalWidth('2xl')
                ->createAnother(false)
                ->successNotificationTitle('Project created successfully!')
                ->form(fn($form) => ProjectResource::form($form)),
        ];
    }

    public static function getProjects()
    {
        return Project::with(['client', 'users'])->latest()->paginate(12);
    }

    public static function projectsInfolist(Schema $schema): Schema
    {
        $projects = self::getProjects();
        return $schema
            ->components(function () use ($projects) {
                if (!empty($projects)) {
                    $projectsData = [];
                    foreach ($projects as $project) {
                        $projectsData[] = Section::make()
                            ->schema([
                                Group::make([

                                    TextEntry::make('name')
                                        ->hiddenLabel()
                                        ->html()
                                        ->url(ProjectResource::getUrl('view', ['record' => $project->id]))
                                        ->default(function () use ($project) {
                                            $prefix = $project->prefix ?? '';
                                            $name = $project->name ?? '-';

                                            $formatted = $prefix
                                                ? "({$prefix}) - <strong>{$name}</strong>"
                                                : "<strong>{$name}</strong>";

                                            return $formatted;
                                        }),


                                    ActionGroup::make([

                                        self::getEditForm($project),

                                        self::getDeleteAction($project),
                                    ])
                                        ->extraAttributes(['style' => 'margin-left: auto;'])


                                ])
                                    ->columns(2),

                                Group::make([

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
                                        }),


                                    TextEntry::make('created_at')
                                        ->hiddenLabel()
                                        ->default('1/345')
                                        ->extraAttributes([
                                            'style' => 'display: flex; justify-content: flex-end;',
                                        ]),

                                ])
                                    ->columns(2),

                                TextEntry::make('progress')
                                    ->hiddenLabel()
                                    ->columnSpanFull()
                                    ->html()
                                    ->default(function () use ($project) {
                                        $progress = $project->progress ?? 0;
                                        $color = $project->color ?? '#3b82f6';

                                        $hex = ltrim($color, '#');
                                        if (strlen($hex) === 3) {
                                            $hex = "{$hex[0]}{$hex[0]}{$hex[1]}{$hex[1]}{$hex[2]}{$hex[2]}";
                                        }

                                        $r = hexdec(substr($hex, 0, 2));
                                        $g = hexdec(substr($hex, 2, 2));
                                        $b = hexdec(substr($hex, 4, 2));

                                        $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

                                        $textColor = $brightness > 155 ? 'black' : 'white';

                                        return <<<HTML
                                                    <div style="position: relative; width: 100%; height: 20px; background-color: #e5e7eb; border-radius: 10px; overflow: hidden; ">
                                                        <div style="position: absolute; left: 0; top: 0; height: 100%; width: 45%; background-color: {$color}; transition: width 0.3s ease;"></div>
                                                        <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 500; color: {$textColor}; ">
                                                            90%
                                                        </div>
                                                    </div>
                                                HTML;
                                    }),

                                ImageEntry::make('users')
                                    ->label('Team')
                                    ->hiddenLabel()
                                    ->default(function () use ($project) {
                                        $users = $project->users ?? collect();

                                        if ($users->isEmpty()) {
                                            $users = \App\Models\User::whereIn('id', function ($query) use ($project) {
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
                                    ->limit(6)
                                    ->limitedRemainingText()
                                    ->imageHeight(40)

                            ]);
                    }
                    return Group::make($projectsData)->columns(3);
                }
            });
    }

    public static function getEditForm(Project $project): Action
    {
        return Action::make('Edit' . $project->id)
            ->label('Edit')
            ->icon('heroicon-s-pencil')
            ->modalHeading("Edit Project")
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
                        ->label('Name')
                        ->placeholder('Name')
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
                        ->label('Prefix')
                        ->placeholder('Prefix')
                        ->maxLength(8)
                        ->reactive()
                        ->unique()
                        ->required(),

                    Select::make('user_ids')
                        ->label('Users')
                        ->multiple()
                        ->options(User::pluck('name', 'id'))
                        ->searchable()
                        ->preload(),


                    Select::make('client_id')
                        ->label('Client')
                        ->options(Client::all()->pluck('name', 'id'))
                        ->preload()
                        ->searchable()
                        ->required(),

                    TextInput::make('price')
                        ->numeric()
                        ->placeholder('Budget')
                        ->label('Budget')
                        ->required(),

                    Select::make('budget_type')
                        ->label('Budget Type')
                        ->options(Project::BUDGET_TYPE)
                        ->native(false)
                        ->hintIcon(Heroicon::QuestionMarkCircle, tooltip: 'Hourly: Amount for task in the invoice will be counted as per hourly rate. eg.02:00 H * 20 rate/Hr
                                                                           Fix Rate: Invoice total amount will be taken as per fixed rate. no hourly calculation of tasks.')
                        ->required(),

                    Group::make([

                        Select::make('currency')
                            ->label('Currency')
                            ->options(Project::CURRENCY)
                            ->native(false)
                            ->required(),

                        Select::make('status')
                            ->label('Status')
                            ->options(Project::STATUS)
                            ->native(false)
                            ->required(),

                        ColorPicker::make('color')
                            ->label('Color')
                            ->placeholder('Color'),

                    ])
                        ->columnSpanFull()
                        ->columns(3),

                    RichEditor::make('description')
                        ->label('Description')
                        ->placeholder('Description')
                        ->columnSpanFull()
                        ->extraAttributes(['style' => 'min-height: 200px;']),


                ])
                    ->columns(2),
            ])
            ->action(function (array $data) use ($project): void {
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
            })
            ->successNotificationTitle('Project updated successfully!');
    }

    public static function getDeleteAction(Project $project): Action
    {
        return Action::make('Delete' . $project->id)
            ->label('Delete')
            ->icon('heroicon-s-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->action(function () use ($project) {
                $project->delete();
            })
            ->after(function ($livewire) {
                $livewire->dispatch('refresh');
            })
            ->successNotificationTitle('Project deleted successfully!');
    }
}
