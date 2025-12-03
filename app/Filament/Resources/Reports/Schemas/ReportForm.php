<?php

namespace App\Filament\Resources\Reports\Schemas;

use App\Models\Client;
use App\Models\Department;
use App\Models\Project;
use App\Models\Report;
use App\Models\Tag;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Hidden::make('owner_id')
                    ->default(auth()->user()->id),

                Section::make()
                    ->schema([

                        Group::make([

                            TextInput::make('name')
                                ->label(__('messages.common.name'))
                                ->placeholder(__('messages.common.name'))
                                ->required(),

                            DatePicker::make('start_date')
                                ->label(__('messages.settings.start_date'))
                                ->placeholder(__('messages.settings.start_date'))
                                ->native(false)
                                ->maxDate(today())
                                ->required(),

                            DatePicker::make('end_date')
                                ->label(__('messages.settings.end_date'))
                                ->placeholder(__('messages.settings.end_date'))
                                ->native(false)
                                ->maxDate(today())
                                ->required()
                                ->after('start_date'),

                        ])
                            ->columns(3)
                            ->columnSpanFull(),

                        Select::make('department_id')
                            ->label(__('messages.users.department'))
                            ->options(Department::query()->pluck('name', 'id'))
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set) {
                                $set('client_id', null);
                                $set('project_ids', []);
                                $set('user_ids', []);
                            })
                            ->afterStateHydrated(
                                fn($state, $set, $record) =>
                                $set('department_id', $record?->departments()?->pluck('departments.id')->first())
                            ),

                        Select::make('client_id')
                            ->label(__('messages.users.client'))
                            ->options(
                                fn(callable $get) =>
                                Client::query()
                                    ->where('department_id', $get('department_id'))
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set) {
                                $set('project_ids', []);
                                $set('user_ids', []);
                            })
                            ->afterStateHydrated(
                                fn($state, $set, $record) =>
                                $set('client_id', $record?->clients()?->pluck('clients.id')->first())
                            ),

                        Select::make('project_ids')
                            ->label(__('messages.projects.projects'))
                            ->multiple()
                            ->searchable()
                            ->options(
                                fn(callable $get) =>
                                Project::query()
                                    ->where('client_id', $get('client_id'))
                                    ->pluck('name', 'id')
                            )
                            ->afterStateHydrated(
                                fn($state, $set, $record) =>
                                $set('project_ids', $record?->projects()?->pluck('projects.id')->toArray())
                            )

                            ->reactive(),

                        Select::make('user_ids')
                            ->label(__('messages.users.users'))
                            ->multiple()
                            ->searchable()
                            ->options(function (callable $get) {
                                $projects = $get('project_ids') ?? [];

                                return User::query()
                                    ->whereHas(
                                        'projects',
                                        fn($q) =>
                                        $q->whereIn('projects.id', $projects)
                                    )
                                    ->where('is_active', true)
                                    ->pluck('name', 'id');
                            })
                            ->afterStateHydrated(
                                fn($state, $set, $record) =>
                                $set('user_ids', $record?->users()?->pluck('users.id')->toArray())
                            ),

                        Select::make('tag_ids')
                            ->label(__('messages.settings.tags'))
                            ->multiple()
                            ->searchable()
                            ->options(Tag::query()->pluck('name', 'id'))
                            ->afterStateHydrated(
                                fn($state, $set, $record) =>
                                $set('tag_ids', $record?->tags()?->pluck('tags.id')->toArray())
                            ),

                        Radio::make('report_type')
                            ->label(__('messages.users.report_type'))
                            ->default(Report::DYNAMIC_REPORT)
                            ->options([
                                Report::DYNAMIC_REPORT => __('messages.users.dynamic'),
                                Report::STATIC_REPORT => __('messages.users.static'),
                            ])
                            ->required()
                            ->inline(),
                    ])
                    ->columnSpanFull()
                    ->columns(2),
            ]);
    }
}
