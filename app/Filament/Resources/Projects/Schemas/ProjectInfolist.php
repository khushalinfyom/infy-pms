<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Filament\Infolists\Components\ProjectActivityLogEntry;
use App\Filament\Infolists\Components\ProjectUserEntry;
use App\Filament\Resources\Projects\Widgets\ProjectProgessChartWidget;
use App\Filament\Resources\Projects\Widgets\ProjectTaskTable;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\View;

class ProjectInfolist
{

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->contained(false)
                    ->tabs([
                        self::getSummaryTab(),

                        self::getActivityTab(),

                        self::getTasksTab(),

                        self::getAttachmentsTab(),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull()
            ]);
    }

    public static function getSummaryTab(): Tab
    {
        return Tab::make('summary')
            ->label(__('messages.projects.summary'))
            ->schema([
                Section::make()
                    ->schema([
                        Group::make([
                            TextEntry::make('name')
                                ->hiddenLabel()
                                ->html()
                                ->formatStateUsing(function ($state, $record) {
                                    return "<b>{$state}</b>" . ' (' . ($record->prefix ?? '') . ')';
                                }),

                            TextEntry::make('status')
                                ->hiddenLabel()
                                ->badge()
                                ->formatStateUsing(fn($state) => Project::STATUS[$state] ?? 'N/A')
                                ->color(fn($state) => match ($state) {
                                    Project::STATUS_ONGOING  => 'primary',
                                    Project::STATUS_FINISHED => 'success',
                                    Project::STATUS_ONHOLD   => 'warning',
                                    Project::STATUS_ARCHIVED => 'info',
                                    default => 'secondary',
                                }),

                            TextEntry::make('description')
                                ->label(__('messages.projects.project_overview'))
                                ->placeholder('N/A')
                                ->html()
                                ->default(fn() => $project->description ?? 'N/A'),
                        ])
                            ->columns(1),
                        Group::make([
                            Livewire::make(ProjectProgessChartWidget::class, fn($record) => [
                                'projectId' => $record->id,
                            ])
                        ])->columns(1),

                    ])->columnSpan(3)
                    ->columns(2)
                    ->extraAttributes(['class' => 'h-full']),

                Group::make([
                    Fieldset::make(__('messages.users.client'))
                        ->schema([
                            SpatieMediaLibraryImageEntry::make('clients')
                                ->collection(Client::IMAGE_PATH)
                                ->circular()
                                ->hiddenLabel()
                                ->width(60)
                                ->height(60)
                                ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->client->name) . '&background=random')
                                ->columns(1),

                            TextEntry::make('client_info')
                                ->label('Client')
                                ->hiddenLabel()
                                ->state(function ($record) {
                                    $client = $record->client;
                                    $text = "<span style='font-size:0.95rem; font-weight:700;'>{$client->name}</span>";
                                    $text .= "\n{$client->email}";
                                    if ($client->department && $client->department->name) {
                                        $text .= "\n" . $client->department->name;
                                    }
                                    return $text;
                                })
                                ->formatStateUsing(fn(string $state) => nl2br($state))
                                ->html()
                                ->columnSpan(2),
                        ])
                        ->columns(3),

                    Section::make()
                        ->schema([
                            IconEntry::make('status')
                                ->hiddenLabel()
                                ->icon('phosphor-money-fill')
                                ->columnSpan(1)
                                ->extraAttributes([
                                    'class' => 'project-side-card',
                                    'style' => 'align-items: center; display: flex; justify-content: center; margin-left: -10px;'
                                ]),

                            TextEntry::make('price')
                                ->label(__('messages.projects.budget'))
                                ->prefix(function ($record) {
                                    return Project::getCurrencyClass($record->currency) . ' ';
                                })
                                ->default(0)
                                ->columnSpan(2),
                        ])
                        ->extraAttributes([
                            'class' => 'project-price-card',
                        ])
                        ->columns(3),

                    Section::make()
                        ->schema([
                            IconEntry::make('status')
                                ->hiddenLabel()
                                ->icon('phosphor-credit-card-fill')
                                ->columnSpan(1)
                                ->extraAttributes([
                                    'class' => 'project-side-card',
                                    'style' => 'align-items: center; display: flex; justify-content: center; margin-left: -10px;'
                                ]),

                            TextEntry::make('budget_type')
                                ->label(__('messages.projects.budget_type'))
                                ->placeholder('N/A')
                                ->formatStateUsing(fn($state) => Project::BUDGET_TYPE[$state] ?? 'N/A')
                                ->columnSpan(2),
                        ])
                        ->extraAttributes([
                            'class' => 'project-budget-type-card',
                        ])
                        ->columns(3),

                    Section::make()
                        ->schema([
                            IconEntry::make('status')
                                ->hiddenLabel()
                                ->icon('phosphor-list-bullets-fill')
                                ->columnSpan(1)
                                ->extraAttributes([
                                    'class' => 'project-side-card',
                                    'style' => 'align-items: center; display: flex; justify-content: center; margin-left: -10px;'
                                ]),

                            TextEntry::make('created_at')
                                ->label(__('messages.projects.tasks'))
                                ->formatStateUsing(function ($state, $record) {
                                    $total = $record->tasks()->count();
                                    $pending = $record->tasks()
                                        ->where('status', Task::STATUS_PENDING)
                                        ->count();

                                    return "{$pending}/{$total} " . __('messages.projects.pending_tasks');
                                })
                                ->columnSpan(2),
                        ])
                        ->extraAttributes([
                            'class' => 'project-created-at-card',
                        ])
                        ->columns(3),
                ]),
                Fieldset::make(__('messages.projects.assigneed_users'))
                    ->schema(function ($record) {
                        $sections = [];

                        foreach ($record->users as $user) {
                            $sections[] = Fieldset::make('')
                                ->schema([
                                    ProjectUserEntry::make("user_{$user->id}")
                                        ->hiddenLabel()
                                        ->user($user),
                                ])
                                ->extraAttributes(['style' => 'display: flex;'])
                                ->columns(2);
                        }

                        return $sections;
                    })
                    ->columnSpanFull()
                    ->columns(4)
            ])->columns(4);
    }

    public static function getActivityTab(): Tab
    {
        return Tab::make('activity')
            ->label(__('messages.projects.activity'))
            ->schema([
                ProjectActivityLogEntry::make('activity_logs')
                    ->state(fn($record) => $record->id)
                    ->hiddenLabel(),
            ]);
    }



    public static function getTasksTab(): Tab
    {
        return Tab::make('tasks')
            ->label(__('messages.projects.tasks'))
            ->schema([
                Livewire::make(ProjectTaskTable::class)
                    ->columnSpanFull(),
            ]);
    }

    public static function getAttachmentsTab(): Tab
    {
        return Tab::make('attachments')
            ->label(__('messages.projects.attachments'))
            ->schema([
                Section::make()
                    ->extraAttributes([
                        'class' => 'attachment-section'
                    ])
                    ->headerActions([
                        Action::make('upload_attachment')
                            ->label(__('messages.projects.upload_attachment'))
                            ->icon('heroicon-s-plus')
                            ->modalWidth('lg')
                            ->form([
                                FileUpload::make('attachment')
                                    ->label(__('messages.projects.select_file'))
                                    ->storeFiles(false)
                                    ->required()
                            ])
                            ->action(function (array $data, $record) {
                                if (!empty($data['attachment'])) {
                                    $record->addMedia($data['attachment'])
                                        ->toMediaCollection(Project::PATH, config('media-library.disk_name'));
                                }
                            })
                    ])
                    ->schema([
                        Group::make([
                            View::make('filament.resources.projects.forms.project-attachments')
                        ])
                    ])
            ]);
    }
}
