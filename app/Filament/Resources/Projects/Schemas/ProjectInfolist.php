<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Filament\Infolists\Components\ProjectUserEntry;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

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

                            TextEntry::make('description')
                                ->label('Project Overview')
                                ->placeholder('N/A')
                                ->html(),
                        ])
                            ->columns(1),

                        Group::make([])->columns(1),

                    ])->columnSpan(3)
                    ->columns(2)
                    ->extraAttributes(['class' => 'h-full']),

                Group::make([
                    Section::make()
                        ->schema([
                            TextEntry::make('client.name')
                                ->label('Client'),
                        ]),
                    Section::make()
                        ->schema([
                            TextEntry::make('price')
                                ->label('Budget')
                                ->prefix('$ ')
                                ->default(0),
                        ]),
                    Section::make()
                        ->schema([
                            TextEntry::make('budget_type')
                                ->label('Budget Type')
                                ->placeholder('N/A')
                                ->formatStateUsing(fn($state) => Project::BUDGET_TYPE[$state] ?? 'N/A'),
                        ]),
                    Section::make()
                        ->schema([
                            TextEntry::make('created_at')
                                ->label('Tasks')
                                ->formatStateUsing(function ($state, $record) {
                                    $total = $record->tasks()->count();
                                    $pending = $record->tasks()
                                        ->where('status', Task::STATUS_PENDING)
                                        ->count();

                                    return "{$pending}/{$total} Pending Tasks";
                                })
                        ])
                ]),
                Section::make()
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
            ->schema([]);
    }

    public static function getTasksTab(): Tab
    {
        return Tab::make('tasks')
            ->label('Tasks')
            ->schema([]);
    }

    public static function getAttachmentsTab(): Tab
    {
        return Tab::make('attachments')
            ->label('Attachments')
            ->schema([]);
    }
}
