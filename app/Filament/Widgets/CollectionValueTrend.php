<?php

namespace App\Filament\Widgets;

use App\Models\CollectionLog;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

class CollectionValueTrend extends ChartWidget
{
    protected ?string $heading = 'Collection value over time';

    protected int|string|array $columnSpan = 'full';

    // Without a cap a full-width line chart grows to its aspect ratio and
    // crowds the rest of the dashboard off the screen.
    protected ?string $maxHeight = '260px';

    protected function getData(): array
    {
        $logs = $this->logs();

        return [
            'datasets' => [
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

    /**
     * The log reaches back to 2003, but everything before 2012 predates any
     * valuation and sits flat at zero. Start the line where the collection
     * first has a used value so the meaningful range isn't squashed.
     *
     * @return Collection<int, CollectionLog>
     */
    protected function logs(): Collection
    {
        return CollectionLog::orderBy('date')
            ->get()
            ->skipWhile(fn (CollectionLog $log): bool => (float) $log->used_value <= 0)
            ->values();
    }

    protected function getType(): string
    {
        return 'line';
    }
}
