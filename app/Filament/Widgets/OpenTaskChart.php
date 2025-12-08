<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class OpenTaskChart extends ApexChartWidget
{
    protected ?string $maxHeight = '400px';

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected static ?string $chartId = 'openTaskChart';

    protected static ?string $heading = 'User Pending Tasks';

    protected static ?int $sort = 3;

    protected function getOptions(): array
    {
        // Get users with their pending task counts
        $userData = DB::table('users')
            ->join('task_assignees', 'users.id', '=', 'task_assignees.user_id')
            ->join('tasks', 'task_assignees.task_id', '=', 'tasks.id')
            ->join('projects', 'tasks.project_id', '=', 'projects.id')
            ->where('tasks.status', Task::STATUS_PENDING) // 0 is pending status
            ->where('projects.deleted_at', null)
            ->where('users.deleted_at', null)
            ->select('users.name as user_name', DB::raw('count(tasks.id) as pending_tasks'))
            ->groupBy('users.id', 'users.name')
            // ->orderBy('pending_tasks', 'desc')
            ->get(); // Removed limit to show all users with pending tasks

        // Extract user names and task counts
        $userNames = $userData->pluck('user_name')->toArray();
        $taskCounts = $userData->pluck('pending_tasks')->toArray();

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
