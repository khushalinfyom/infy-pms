<?php

namespace App\Filament\Resources\Projects\Widgets;

use App\Models\Status;
use App\Models\Task;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Illuminate\Support\Facades\DB;

class ProjectProgessChartWidget extends ApexChartWidget
{
    public ?int $projectId = null;

    protected static ?string $chartId = 'dynamic-task-status-chart';

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getHeading(): string
    {
        return '';
    }

    public $colors = [
        '#c4c9ffff',
        '#f5b9fbff',
        '#e1c9ffff',
        '#ffb3bfff',
        '#7db7f1ff',
        '#00d4df',
        '#3bd06d',
        '#32dac2',
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

    protected function getOptions(): array
    {
        $statusCounts = Task::where('project_id', $this->projectId)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->get();

        $labels = [];
        $series = [];
        $colors = [];

        foreach ($statusCounts as $index => $row) {

            $statusId = $row->status;
            $count    = $row->total;

            $status = Status::find($statusId);
            $statusName = $status ? $status->name : 'Unknown Status';

            $labels[] = $statusName;
            $series[] = $count;

            $colors[] = $this->colors[$index % count($this->colors)];
        }

        if (empty($labels)) {
            $labels = ['No Tasks Found'];
            $series = [100];
            $colors = ['#ddd'];
        }

        return [
            'chart' => [
                'type'   => 'pie',
                'height' => 400,
            ],
            'labels' => $labels,
            'series' => $series,
            'colors' => $colors,
            'legend' => [
                'position' => 'bottom',
            ],
        ];
    }
}
