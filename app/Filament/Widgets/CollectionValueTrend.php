<?php

namespace App\Filament\Widgets;

use App\Models\CollectionLog;
use Filament\Widgets\ChartWidget;

class CollectionValueTrend extends ChartWidget
{
    protected ?string $heading = 'Collection value over time';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $logs = CollectionLog::orderBy('date')->get();

        return [
            'datasets' => [
                [
                    'label' => 'New value',
                    'data' => $logs->pluck('new_value')->all(),
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Used value',
                    'data' => $logs->pluck('used_value')->all(),
                    'borderColor' => '#d97706',
                    'backgroundColor' => 'rgba(217, 119, 6, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $logs->map(fn (CollectionLog $log): string => $log->date->format('M Y'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
