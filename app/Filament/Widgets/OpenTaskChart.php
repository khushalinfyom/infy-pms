<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class OpenTaskChart extends ApexChartWidget
{
    use HasFiltersSchema;

    public ?array $userData = [];
    public ?array $selectedUsers = [];

    protected ?string $maxHeight = '400px';

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected static ?string $chartId = 'openTaskChart';

    protected static ?string $heading = 'User Pending Tasks';

    protected static ?int $sort = 3;

    public function mount(): void
    {
        $this->userData = DB::table('users')
            ->join('task_assignees', 'users.id', '=', 'task_assignees.user_id')
            ->join('tasks', 'task_assignees.task_id', '=', 'tasks.id')
            ->join('projects', 'tasks.project_id', '=', 'projects.id')
            ->where('tasks.status', Task::STATUS_PENDING)
            ->whereNull('projects.deleted_at')
            ->whereNull('users.deleted_at')
            ->select(
                'users.id as user_id',
                'users.name as user_name',
                DB::raw('COUNT(tasks.id) as pending_tasks')
            )
            ->groupBy('users.id', 'users.name')
            ->get()
            ->toArray();

        $this->selectedUsers = collect($this->userData)
            ->pluck('user_id')
            ->take(20)
            ->toArray();

        $this->filters = [
            'users' => $this->selectedUsers
        ];

        if (method_exists($this, 'getFiltersSchema')) {
            $this->getFiltersSchema()->fill();
        }

        $this->options = $this->getOptions();

        if (! $this->getDeferLoading()) {
            $this->readyToLoad = true;
        }
    }

    public function filtersSchema(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('users')
                ->label('Select Users')
                ->multiple()
                ->native(false)
                ->live()
                ->maxItems(30)
                ->options(collect($this->userData)->pluck('user_name', 'user_id')->toArray())
                ->default($this->selectedUsers),
        ]);
    }

    public function updatedInteractsWithSchemas(string $statePath): void
    {
        $this->selectedUsers = $this->filters['users'] ?? [];
        $this->updateOptions();
    }

    protected function getOptions(): array
    {
        $filteredData = collect($this->userData)
            ->filter(fn($u) => in_array($u->user_id, $this->selectedUsers))
            ->values();

        $userNames = $filteredData->pluck('user_name')->toArray();
        $taskCounts = $filteredData->pluck('pending_tasks')->toArray();

        $colors = [
            '#5b65d4',
            '#32dac2',
            '#684395',
            '#d885e0',
            '#4692df',
            '#3bd06d',
            '#dc4a60',
            '#00d4df',
            '#e6d5bd',
            '#e09c8d',
            '#96d1d5',
            '#e6c1ce',
            '#e1b6ae',
            '#e6bce6',
            '#abd3e0',
            '#8baee2',
            '#b87da8',
            '#e6e0b9',
            '#5b65d4',
            '#684395',
            '#d885e0',
            '#dc4a60',
            '#4692df',
            '#00d4df',
            '#3bd06d',
            '#32dac2',
            '#e6d5bd',
            '#e09c8d',
            '#96d1d5',
            '#e6c1ce',
        ];

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 400,
            ],
            'series' => [
                [
                    'name' => 'Pending Tasks',
                    'data' => $taskCounts,
                ],
            ],
            'xaxis' => [
                'categories' => $userNames,
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'colors' => $colors,
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 3,
                    'horizontal' => false, // Changed to vertical bars for better readability
                ],
            ],
        ];
    }
}
