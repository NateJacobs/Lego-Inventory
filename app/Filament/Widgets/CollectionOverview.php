<?php

namespace App\Filament\Widgets;

use App\Services\CollectionValuation;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CollectionOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Collection value';

    protected function getStats(): array
    {
        // Same source of truth the Nova metrics and the monthly snapshot use.
        $valuation = app(CollectionValuation::class);

        return [
            Stat::make('Current value (new)', '$'.number_format($valuation->newValue(), 2))
                ->description('At BrickLink new prices')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Current value (used)', '$'.number_format($valuation->usedValue(), 2))
                ->description('At BrickLink used prices')
                ->color('warning'),

            Stat::make('Retail value', '$'.number_format($valuation->retailValue(), 2))
                ->description('Original MSRP'),

            Stat::make('Sets owned', number_format($valuation->totalSets()))
                ->description(number_format($valuation->pieceCount()).' pieces')
                ->descriptionIcon('heroicon-m-squares-2x2'),
        ];
    }
}
