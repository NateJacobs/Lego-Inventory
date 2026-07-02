<?php

namespace App\Nova\Dashboards;

use App\Nova\Metrics\CollectionTrendByYear;
use App\Nova\Metrics\TotalCollectionNewValue;
use App\Nova\Metrics\TotalCollectionPiecePartition;
use App\Nova\Metrics\TotalCollectionUsedValue;
use App\Nova\Metrics\TotalMinifigs;
use App\Nova\Metrics\TotalSets;
use Laravel\Nova\Dashboards\Main as Dashboard;

class Main extends Dashboard
{
    /**
     * Get the cards for the dashboard.
     *
     * @return array
     */
    public function cards()
    {
        return [
            (new CollectionTrendByYear)->width('1/2'),
            (new TotalCollectionPiecePartition)->width('1/2'),
            (new TotalSets)->width('1/4'),
            (new TotalMinifigs)->width('1/4'),
            (new TotalCollectionUsedValue)->width('1/4'),
            (new TotalCollectionNewValue)->width('1/4'),
        ];
    }
}
