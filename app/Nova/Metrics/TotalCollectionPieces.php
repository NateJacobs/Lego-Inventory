<?php

namespace App\Nova\Metrics;

use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Value;

class TotalCollectionPieces extends Value
{
    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function calculate(Request $request)
    {
        $sets = \App\Models\CatalogItem::join(
            'sets', 'catalog_items.id', '=', 'sets.catalog_item_id'
            )->get();
        $sets_count = $sets->sum('piece_count');

        $bulk_brick = \App\Models\BulkBrick::all();
        $bulk_count = $bulk_brick->sum('piece_count');

        $bricklink = \App\Models\BricklinkOrder::all();
        $bricklink_count = $bricklink->sum('pieces');

        $result = new \Laravel\Nova\Metrics\ValueResult($sets_count + $bulk_count + $bricklink_count);
        return $result->suffix('pieces')->format('0,0');
    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array
     */
    public function ranges()
    {
        return [];
    }

    /**
     * Determine for how many minutes the metric should be cached.
     *
     * @return  \DateTimeInterface|\DateInterval|float|int
     */
    public function cacheFor()
    {
        return now()->addMinutes(15);
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'total-collection-pieces';
    }
}
