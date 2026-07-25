<?php

namespace App\Filament\Widgets;

use App\Services\CollectionValuation;
use Filament\Widgets\ChartWidget;

class PiecesBySegment extends ChartWidget
{
    protected ?string $heading = 'Pieces by segment';

    protected function getData(): array
    {
        $breakdown = app(CollectionValuation::class)->pieceCountBreakdown();

        return [
            'datasets' => [
                [
                    'data' => array_values($breakdown),
                    'backgroundColor' => ['#2563eb', '#d97706', '#16a34a'],
                ],
            ],
            'labels' => array_keys($breakdown),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
