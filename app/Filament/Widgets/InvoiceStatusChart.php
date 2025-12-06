<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class InvoiceStatusChart extends ApexChartWidget
{
    protected static ?string $chartId = 'invoiceStatusChart';

    protected static ?string $heading = 'Invoice Status';

    public $colors = [
        '#d77cd1ff',
        '#8baee2',
        '#3bd06d',
    ];

    protected function getOptions(): array
    {
        // Get count of invoices for each status
        $statusCounts = Invoice::select('status', DB::raw('count(*) as count'))
            ->whereIn('status', [
                Invoice::STATUS_DRAFT,
                Invoice::STATUS_SENT,
                Invoice::STATUS_PAID
            ])
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Prepare data for the chart
        $statuses = [
            Invoice::STATUS_DRAFT => 'Draft',
            Invoice::STATUS_SENT => 'Sent',
            Invoice::STATUS_PAID => 'Paid'
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
