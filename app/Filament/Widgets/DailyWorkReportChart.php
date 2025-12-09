<?php

namespace App\Filament\Widgets;

use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class DailyWorkReportChart extends ApexChartWidget
{
    use HasFiltersSchema;

    protected ?string $maxHeight = '400px';

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected static ?string $chartId = 'dailyWorkReportChart';

    protected static ?string $heading = 'Daily Work Report';

    protected static ?int $sort = 2;

    protected function getChartContainerAttributes(): array
    {
        return [
            'style' => '
                overflow-x: auto;
                overflow-y: hidden;
                white-space: nowrap;
            ',
        ];
    }

    public function updatedInteractsWithSchemas(string $statePath): void
    {
        $this->updateOptions();
    }

    public function filtersSchema(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('date')
                ->label('Select Date')
                ->placeholder('Select Date')
                ->icon('heroicon-o-calendar')
                ->default(Carbon::today())
                ->live()
                ->native(false)
                ->maxDate(Carbon::now())
                ->afterStateUpdated(fn() => $this->updateOptions())
                ->extraAttributes(['class' => 'no-clear']),
        ]);
    }

    protected function getOptions(): array
    {
        $selectedDate = $this->filters['date'] ?? Carbon::today();

        if (is_string($selectedDate)) {
            $selectedDate = Carbon::parse($selectedDate);
        }

        // Get all active users (regardless of whether they have time entries)
        $allUsers = User::where('users.is_active', 1)
            ->whereNotNull('users.email_verified_at')
            ->whereNotNull('users.password')
            ->where('users.set_password', 1)
            ->where('users.is_email_verified', 1)
            ->whereNull('users.deleted_by')
            ->whereNull('users.deleted_at')
            ->select('id', 'name')
            ->get();

        // Get time entries for the selected date
        $timeEntries = TimeEntry::select(
            'time_entries.user_id',
            DB::raw('SUM(time_entries.duration) as total_duration')
        )
            ->whereDate('time_entries.created_at', $selectedDate)
            ->groupBy('time_entries.user_id')
            ->get()
            ->keyBy('user_id');

        // Prepare data for the chart - include all users, even those without time entries
        $userNames = [];
        $durations = [];

        foreach ($allUsers as $user) {
            $userNames[] = $user->name;
            // If user has time entries, use that duration, otherwise 0
            $durations[] = isset($timeEntries[$user->id]) ? (float) $timeEntries[$user->id]->total_duration : 0;
        }

        // Fixed color palette
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
        ];

        $chartWidth = max(800, count($userNames) * 100);

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 400,
                // 'width' => 4000,
                'width' => $chartWidth,
                'toolbar' => [
                    'show' => false,
                ],
                'animations' => [
                    'enabled' => true,
                    'easing' => 'easeinout',
                    'speed' => 800,
                    'animateGradually' => [
                        'enabled' => true,
                        'delay' => 150,
                    ],
                    'dynamicAnimation' => [
                        'enabled' => true,
                        'speed' => 350,
                    ],
                ],
                'zoom' => [
                    'enabled' => false,
                ],
                'dropShadow' => [
                    'enabled' => true,
                    'color' => '#000',
                    'top' => 18,
                    'left' => 7,
                    'blur' => 10,
                    'opacity' => 0.1,
                ],
            ],
            'series' => [
                [
                    'name' => 'Time Duration (In Minutes)',
                    'data' => $durations,
                ],
            ],
            'xaxis' => [
                'categories' => $userNames,
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                        'fontWeight' => 500,
                        'fontSize' => '12px',
                        'colors' => ['#64748b'],
                    ],
                    'rotate' => count($userNames) > 5 ? -45 : 0,
                ],
                'axisBorder' => [
                    'show' => false,
                ],
                'axisTicks' => [
                    'show' => false,
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                        'fontWeight' => 500,
                        'fontSize' => '12px',
                        'colors' => ['#64748b'],
                    ],
                    'formatter' => 'function (value) { return Number.isInteger(value) ? value : value.toFixed(2); }',
                ],
                'forceNiceScale' => true,
                'tickAmount' => 6,
                'min' => 0,
                'axisBorder' => [
                    'show' => false,
                ],
                'axisTicks' => [
                    'show' => false,
                ],
            ],
            'colors' => array_slice($colors, 0, count($userNames)),
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 8,
                    'borderRadiusApplication' => 'end',
                    'horizontal' => false,
                    'columnWidth' => '70%',
                ],
            ],
            'fill' => [
                'type' => 'gradient',
                'gradient' => [
                    'shade' => 'light',
                    'type' => 'vertical',
                    'shadeIntensity' => 0.4,
                    'inverseColors' => false,
                    'opacityFrom' => 1,
                    'opacityTo' => 0.7,
                    'stops' => [0, 100],
                ],
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
            'stroke' => [
                'show' => true,
                'width' => 2,
                'colors' => ['rgba(255,255,255,0.3)'],
            ],
            'legend' => [
                'show' => false,
            ],
            'tooltip' => [
                'enabled' => true,
                'style' => [
                    'fontSize' => '14px',
                    'fontFamily' => 'inherit',
                ],
                'y' => [
                    'formatter' => "function(value) { return value + ' minutes'; }",
                ],
            ],
            'grid' => [
                'show' => true,
                'borderColor' => '#e2e8f0',
                'strokeDashArray' => 4,
                'xaxis' => [
                    'lines' => [
                        'show' => false,
                    ],
                ],
                'yaxis' => [
                    'lines' => [
                        'show' => true,
                    ],
                ],
                'padding' => [
                    'top' => 0,
                    'right' => 0,
                    'bottom' => 0,
                    'left' => 0,
                ],
            ],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<JS
        {
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return val;
                    }
                }
            }
        }
    JS);
    }
}
