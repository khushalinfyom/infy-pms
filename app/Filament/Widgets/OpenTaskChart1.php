<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use App\Models\User;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class OpenTaskChart1 extends ApexChartWidget
{
    protected ?string $maxHeight = '400px';

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected static ?string $chartId = 'openTaskChart1';

    protected static ?string $heading = 'Open Task deleted';

    protected function getOptions(): array
    {
        // Get open tasks data grouped by project and user
        $tasks = Task::with(['project', 'taskAssignee'])->whereStatus(Task::STATUS_PENDING)->get();

        $userNames = [];
        $projects = [];

        /** @var Task $task */
        foreach ($tasks as $task) {
            $projectName = $task->project->name;
            $projectColor = $task->project->color ?? '#f59e0b';
            $projectId = $task->project->id;

            if (! isset($projects[$projectName])) {
                $projects[$projectName] = [
                    'name' => $projectName,
                    'color' => $projectColor,
                    'id' => $projectId,
                    'users' => []
                ];
            }

            $taskAssignees = $task->taskAssignee;
            /** @var User $taskAssignee */
            foreach ($taskAssignees as $taskAssignee) {
                $userName = $taskAssignee->name;
                if (! in_array($userName, $userNames)) {
                    $userNames[] = $userName;
                }

                if (! isset($projects[$projectName]['users'][$userName])) {
                    $projects[$projectName]['users'][$userName] = 0;
                }
                $projects[$projectName]['users'][$userName]++;
            }
        }

        // Get all user names
        $allUserNames = array_values($userNames);

        // Restructure data for chart - each project gets a series
        $series = [];
        $projectColors = [];

        foreach ($projects as $project) {
            $projectData = [
                'name' => $project['name'],
                'data' => []
            ];

            foreach ($allUserNames as $userName) {
                $projectData['data'][] = $project['users'][$userName] ?? 0;
            }

            // Only add series if it has data
            if (array_sum($projectData['data']) > 0) {
                $series[] = $projectData;
                $projectColors[] = $project['color'];
            }
        }

        return [
            // 'chart' => [
            //     'type' => 'bar',
            //     'height' => 400,
            //     'stacked' => true,
            //     'toolbar' => [
            //         'show' => false,
            //     ],
            //     'animations' => [
            //         'enabled' => true,
            //         'easing' => 'easeinout',
            //         'speed' => 800,
            //         'animateGradually' => [
            //             'enabled' => true,
            //             'delay' => 150,
            //         ],
            //         'dynamicAnimation' => [
            //             'enabled' => true,
            //             'speed' => 350,
            //         ],
            //     ],
            //     'zoom' => [
            //         'enabled' => false,
            //     ],
            //     'dropShadow' => [
            //         'enabled' => true,
            //         'color' => '#000',
            //         'top' => 18,
            //         'left' => 7,
            //         'blur' => 10,
            //         'opacity' => 0.1,
            //     ],
            // ],
            // 'colors' => $projectColors,
            // 'series' => $series,
            // 'xaxis' => [
            //     'categories' => $allUserNames,
            //     'labels' => [
            //         'style' => [
            //             'fontFamily' => 'inherit',
            //             'fontWeight' => 500,
            //             'fontSize' => '12px',
            //             'colors' => ['#64748b'],
            //         ],
            //         'rotate' => count($allUserNames) > 5 ? -45 : 0,
            //     ],
            //     'axisBorder' => [
            //         'show' => false,
            //     ],
            //     'axisTicks' => [
            //         'show' => false,
            //     ],
            // ],
            // 'yaxis' => [
            //     'labels' => [
            //         'style' => [
            //             'fontFamily' => 'inherit',
            //             'fontWeight' => 500,
            //             'fontSize' => '12px',
            //             'colors' => ['#64748b'],
            //         ],
            //         'formatter' => "function(value) { return value; }",
            //     ],
            //     'forceNiceScale' => true,
            //     'tickAmount' => 6,
            //     'min' => 0,
            //     'axisBorder' => [
            //         'show' => false,
            //     ],
            //     'axisTicks' => [
            //         'show' => false,
            //     ],
            // ],
            // 'plotOptions' => [
            //     'bar' => [
            //         'borderRadius' => 8,
            //         'borderRadiusApplication' => 'end',
            //         'horizontal' => false,
            //         'columnWidth' => '40%',
            //     ],
            // ],
            // 'fill' => [
            //     'type' => 'gradient',
            //     'gradient' => [
            //         'shade' => 'light',
            //         'type' => 'vertical',
            //         'shadeIntensity' => 0.4,
            //         'inverseColors' => false,
            //         'opacityFrom' => 1,
            //         'opacityTo' => 0.7,
            //         'stops' => [0, 100],
            //     ],
            // ],
            // 'dataLabels' => [
            //     'enabled' => false,
            // ],
            // 'stroke' => [
            //     'show' => true,
            //     'width' => 2,
            //     'colors' => ['rgba(255,255,255,0.3)'],
            // ],
            // 'legend' => [
            //     'show' => true,
            //     'position' => 'bottom',
            //     'fontFamily' => 'inherit',
            // ],
            // 'tooltip' => [
            //     'enabled' => true,
            //     'style' => [
            //         'fontSize' => '14px',
            //         'fontFamily' => 'inherit',
            //     ],
            //     'y' => [
            //         'formatter' => "function(value) { return value + ' tasks'; }",
            //     ],
            // ],
            // 'grid' => [
            //     'show' => true,
            //     'borderColor' => '#e2e8f0',
            //     'strokeDashArray' => 4,
            //     'xaxis' => [
            //         'lines' => [
            //             'show' => false,
            //         ],
            //     ],
            //     'yaxis' => [
            //         'lines' => [
            //             'show' => true,
            //         ],
            //     ],
            //     'padding' => [
            //         'top' => 0,
            //         'right' => 0,
            //         'bottom' => 0,
            //         'left' => 0,
            //     ],
            // ],
        ];
    }
}
