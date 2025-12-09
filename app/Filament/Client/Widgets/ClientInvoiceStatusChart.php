<?php

namespace App\Filament\Client\Widgets;

use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ClientInvoiceStatusChart extends ApexChartWidget
{
    protected static ?string $heading = 'Invoice Status';

    protected static ?int $sort = 2;

    public $colors = [
        '#8baee2',
        '#3bd06d',
    ];
    protected static ?string $chartId = 'clientInvoiceStatusChart';

    protected function getOptions(): array
    {
        $clientId = Auth::id();

        $statusCounts = Invoice::select('status', DB::raw('count(*) as count'))
            ->join('invoice_clients', 'invoices.id', '=', 'invoice_clients.invoice_id')
            ->where('invoice_clients.client_id', $clientId)
            ->whereIn('status', [
                Invoice::STATUS_SENT,
                Invoice::STATUS_PAID
            ])
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $statuses = [
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
                'type' => 'donut',
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
            'plotOptions' => [
                'pie' => [
                    'donut' => [
                        'size' => '50%',
                    ],
                ],
            ],
        ];
    }
}
