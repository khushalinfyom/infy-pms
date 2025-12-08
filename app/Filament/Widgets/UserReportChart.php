<?php

namespace App\Filament\Widgets;

use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class UserReportChart extends ApexChartWidget
{
    use HasFiltersSchema;

    protected ?string $maxHeight = '400px';

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected static ?string $chartId = 'userReportChart';

    protected static ?int $sort = 1;

    public function updatedInteractsWithSchemas(string $statePath): void
    {
        $this->updateOptions();
    }

    public function filtersSchema(Schema $schema): Schema
    {
        $start = Carbon::now()->setTimezone(date_default_timezone_get())->subDays(6)->startOfDay()->format('d/m/Y');
        $end = Carbon::now()->setTimezone(date_default_timezone_get())->endOfDay()->format('d/m/Y');
        return $schema->components([
            DateRangePicker::make('date_range')
                ->label('Select Date Range')
                ->placeholder('Select Date Range')
                ->icon('phosphor-arrows-clockwise')
                ->default("$start - $end")
                ->live()
                ->extraInputAttributes([
                    'class' => 'dark:bg-gray-800',
                ])
                ->afterStateUpdated(fn(callable $set, $state) => $this->updateDateRange($set, $state)),

            Select::make('user_id')
                ->label('Select User')
                ->options(User::pluck('name', 'id'))
                ->default(Auth::id())
                ->searchable()
                ->preload()
                ->native(false)
                ->live()
                ->afterStateUpdated(fn() => $this->updateOptions())
                ->extraAttributes(['class' => 'no-clear']),
        ]);
    }

    protected function updateDateRange(callable $set, $state): void
    {
        if (! $state) {
            return;
        }

        $dates = explode(' - ', $state);

        if (count($dates) !== 2) {
            return;
        }

        try {
            $start_date = Carbon::createFromFormat('d/m/Y', $dates[0])->setTimezone(date_default_timezone_get())->startOfDay()->format('Y-m-d');
            $end_date = Carbon::createFromFormat('d/m/Y', $dates[1])->setTimezone(date_default_timezone_get())->endOfDay()->format('Y-m-d');
            $set('date_start', $start_date);
            $set('date_end', $end_date);

            $this->updateOptions();
        } catch (\Exception $e) {
            // Handle date parsing errors silently
        }
    }

    protected function getOptions(): array
    {
        $dateRange = $this->filters['date_range'] ?? null;
        $userId = $this->filters['user_id'] ?? null;

        $start_date = Carbon::now()->setTimezone(date_default_timezone_get())->subDays(6)->startOfDay();
        $end_date = Carbon::now()->setTimezone(date_default_timezone_get())->endOfDay();

        if ($dateRange) {
            $dates = explode(' - ', $dateRange);
            if (count($dates) === 2) {
                try {
                    $start_date = Carbon::createFromFormat('d/m/Y', $dates[0])->setTimezone(date_default_timezone_get())->startOfDay();
                    $end_date = Carbon::createFromFormat('d/m/Y', $dates[1])->setTimezone(date_default_timezone_get())->endOfDay();
                } catch (\Exception $e) {
                    $start_date = Carbon::now()->setTimezone(date_default_timezone_get())->subDays(6)->startOfDay();
                    $end_date = Carbon::now()->setTimezone(date_default_timezone_get())->endOfDay();
                }
            }
        }

        $daysDiff = $start_date->diffInDays($end_date);
        $groupByDay = $daysDiff <= 90;

        // Create all periods in the range
        $allPeriods = collect();
        if ($groupByDay) {
            $current = $start_date->copy();
            while ($current <= $end_date) {
                $allPeriods->push($current->format('Y-m-d'));
                $current->addDay();
            }

            $formattedPeriods = $allPeriods->map(function ($period) {
                return Carbon::createFromFormat('Y-m-d', $period)->format('M d');
            })->toArray();
        } else {
            // Group by month
            $current = $start_date->copy()->startOfMonth();
            $endMonth = $end_date->copy()->endOfMonth();

            while ($current <= $endMonth) {
                $allPeriods->push($current->format('Y-m'));
                $current->addMonth();
            }

            $formattedPeriods = $allPeriods->map(function ($period) {
                $date = Carbon::createFromFormat('Y-m', $period);
                return $date->format('M Y');
            })->toArray();
        }

        // Query time entries grouped by period and project with duration > 0
        if ($groupByDay) {
            $timeEntryQuery = TimeEntry::select(
                DB::raw('DATE(time_entries.created_at) as period'),
                'projects.name as project_name',
                DB::raw('SUM(time_entries.duration) as total_duration')
            )
                ->join('tasks', 'time_entries.task_id', '=', 'tasks.id')
                ->join('projects', 'tasks.project_id', '=', 'projects.id')
                ->where('time_entries.created_at', '>=', $start_date)
                ->where('time_entries.created_at', '<=', $end_date)
                ->where('time_entries.duration', '>', 0); // Only entries with duration > 0
        } else {
            $timeEntryQuery = TimeEntry::select(
                DB::raw('DATE_FORMAT(time_entries.created_at, "%Y-%m") as period'),
                'projects.name as project_name',
                DB::raw('SUM(time_entries.duration) as total_duration')
            )
                ->join('tasks', 'time_entries.task_id', '=', 'tasks.id')
                ->join('projects', 'tasks.project_id', '=', 'projects.id')
                ->where('time_entries.created_at', '>=', $start_date)
                ->where('time_entries.created_at', '<=', $end_date)
                ->where('time_entries.duration', '>', 0); // Only entries with duration > 0
        }

        // Apply user filter if selected
        if ($userId) {
            $timeEntryQuery->where('time_entries.user_id', $userId);
        }

        $timeEntryData = $timeEntryQuery->groupBy('period', 'projects.name')
            ->orderBy('period')
            ->get();

        // Get projects that have duration > 0
        $projects = $timeEntryData->pluck('project_name')->unique()->values();

        // Initialize series data for each project
        $series = [];
        foreach ($projects as $project) {
            $series[] = [
                'name' => $project,
                'data' => array_fill(0, count($allPeriods), 0)
            ];
        }

        // Populate series data
        foreach ($timeEntryData as $entry) {
            $periodIndex = $allPeriods->search($entry->period);
            $projectIndex = $projects->search($entry->project_name);

            if ($periodIndex !== false && $projectIndex !== false) {
                $series[$projectIndex]['data'][$periodIndex] = (float) $entry->total_duration;
            }
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
                'stacked' => true,
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
            'series' => $series,
            'xaxis' => [
                'categories' => $formattedPeriods,
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                        'fontWeight' => 500,
                        'fontSize' => '12px',
                        'colors' => ['#64748b'],
                    ],
                    'rotate' => $groupByDay && count($formattedPeriods) > 10 ? -45 : 0,
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
            'colors' => $colors,
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 8,
                    'borderRadiusApplication' => 'end',
                    'horizontal' => false,
                    'columnWidth' => $groupByDay ? '70%' : '50%',
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
                'show' => true,
                'position' => 'bottom',
                'fontFamily' => 'inherit',
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

    protected function getHeading(): string
    {
        return 'User Report';
    }
}
