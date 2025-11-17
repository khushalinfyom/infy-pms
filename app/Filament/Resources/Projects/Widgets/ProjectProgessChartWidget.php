<?php

namespace App\Filament\Resources\Projects\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ProjectProgessChartWidget extends ApexChartWidget
{
    protected static ?string $chartId = 'test-static-chart';

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getHeading(): string
    {
        return 'Demo Pie Chart';
    }

    protected function getOptions(): array
    {
        return [
            'chart' => [
                'type' => 'pie',
                'height' => 350,
            ],

            'labels' => ['Red', 'Blue', 'Green'],

            'series' => [44, 55, 13],

            'legend' => [
                'position' => 'bottom',
            ],

            'colors' => ['#FF4560', '#008FFB', '#00E396'],
        ];
    }
}
