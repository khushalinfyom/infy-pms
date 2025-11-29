<?php

namespace App\Filament\Client\Resources\Projects\Schemas;

use App\Filament\Infolists\Components\ProjectActivityLogEntry;
use App\Filament\Infolists\Components\ProjectUserEntry;
use App\Filament\Resources\Projects\Widgets\ProjectProgessChartWidget;
use App\Filament\Resources\Projects\Widgets\ProjectTaskTable;
use App\Models\Project;
use App\Models\Task;
use Filament\Infolists\Components\IconEntry;
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
            ->label('Summary')
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

                            TextEntry::make('price')
                                ->label('Budget')
                                ->formatStateUsing(function ($record) {
                                    $currency = Project::getCurrencyClass($record->currency);
                                    $price = $record->price ?? 0;
                                    $budgetType = Project::BUDGET_TYPE[$record->budget_type] ?? 'N/A';

                                    return "{$currency} {$price} / {$budgetType}";
                                })
                                ->placeholder('N/A')
                                ->columnSpan(2),

                            TextEntry::make('description')
                                ->label('Project Overview')
                                ->placeholder('N/A')
                                ->html()
                                ->columnSpanFull()
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

                    Section::make()
                        ->schema([
                            IconEntry::make('status')
                                ->hiddenLabel()
                                ->icon('phosphor-list-checks-fill')
                                ->columnSpan(1)
                                ->extraAttributes([
                                    'class' => 'project-side-card',
                                    'style' => 'align-items: center; display: flex; justify-content: center; margin-left: -10px;'
                                ]),

                            TextEntry::make('created_at')
                                ->label('Total Tasks')
                                ->formatStateUsing(function ($record) {
                                    $total = $record->tasks()->count();
                                    return "{$total}";
                                })
                                ->columnSpan(2),
                        ])
                        ->extraAttributes([
                            'class' => 'project-total-task-card',
                        ])
                        ->columns(3),

                    Section::make()
                        ->schema([
                            IconEntry::make('status')
                                ->hiddenLabel()
                                ->icon('phosphor-check-circle-fill')
                                ->columnSpan(1)
                                ->extraAttributes([
                                    'class' => 'project-side-card',
                                    'style' => 'align-items: center; display: flex; justify-content: center; margin-left: -10px;'
                                ]),

                            TextEntry::make('created_at')
                                ->label('Completed Tasks')
                                ->formatStateUsing(function ($record) {
                                    $completed = $record->tasks()
                                        ->where('status', Task::STATUS_COMPLETED)
                                        ->count();

                                    return "{$completed}";
                                })
                                ->columnSpan(2),
                        ])
                        ->extraAttributes([
                            'class' => 'project-completed-task-card',
                        ])
                        ->columns(3),

                    Section::make()
                        ->schema([
                            IconEntry::make('status')
                                ->hiddenLabel()
                                ->icon('phosphor-clock-fill')
                                ->columnSpan(1)
                                ->extraAttributes([
                                    'class' => 'project-side-card',
                                    'style' => 'align-items: center; display: flex; justify-content: center; margin-left: -10px;'
                                ]),

                            TextEntry::make('created_at')
                                ->label('Pending Tasks')
                                ->formatStateUsing(function ($state, $record) {
                                    $pending = $record->tasks()
                                        ->where('status', Task::STATUS_PENDING)
                                        ->count();

                                    return "{$pending}";
                                })
                                ->columnSpan(2),
                        ])
                        ->extraAttributes([
                            'class' => 'project-pending-task-card',
                        ])
                        ->columns(3),
                ]),
                Fieldset::make('Project Members')
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
            ->label('Activity')
            ->schema([
                ProjectActivityLogEntry::make('activity_logs')
                    ->state(fn($record) => $record->id)
                    ->hiddenLabel(),
            ]);
    }

    public static function getTasksTab(): Tab
    {
        return Tab::make('tasks')
            ->label('Tasks')
            ->schema([
                Livewire::make(ProjectTaskTable::class)
                    ->columnSpanFull(),
            ]);
    }

    public static function getAttachmentsTab(): Tab
    {
        return Tab::make('attachments')
            ->label('Attachments')
            ->schema([
                Section::make()
                    ->extraAttributes([
                        'class' => 'attachment-section'
                    ])
                    ->headerActions([
                        Action::make('upload_attachment')
                            ->label('Upload Attachment')
                            ->icon('heroicon-s-plus')
                            ->modalWidth('lg')
                            ->form([
                                FileUpload::make('attachment')
                                    ->label('Select File')
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
