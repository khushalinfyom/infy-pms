<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ProjectStatusChart extends ApexChartWidget
{
    protected static ?string $chartId = 'projectStatusChart';

    protected static ?string $heading = 'Project Status';

    public $colors = [
        '#8baee2',
        '#3bd06d',
        '#dc4a60',
        '#e09c8d',
    ];

    protected function getOptions(): array
    {
        // Get count of projects for each status
        $statusCounts = Project::select('status', DB::raw('count(*) as count'))
            ->whereIn('status', [
                Project::STATUS_ONGOING,
                Project::STATUS_FINISHED,
                Project::STATUS_ONHOLD,
                Project::STATUS_ARCHIVED
            ])
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Prepare data for the chart
        $statuses = [
            Project::STATUS_ONGOING => 'Ongoing',
            Project::STATUS_FINISHED => 'Finished',
            Project::STATUS_ONHOLD => 'On Hold',
            Project::STATUS_ARCHIVED => 'Archived'
        ];

        $series = [];
        $labels = [];

        foreach ($statuses as $statusId => $statusName) {
            $series[] = $statusCounts[$statusId] ?? 0;
            $labels[] = $statusName;
        }

        return [
            'chart' => [
                'type' => 'pie',
                'height' => 300,
            ],
            'series' => $series,
            'labels' => $labels,
            'colors' => $this->colors,
            'legend' => [
                'labels' => [
                    'fontFamily' => 'inherit',
                ],
                'position' => 'bottom',
            ],
        ];
    }
}
