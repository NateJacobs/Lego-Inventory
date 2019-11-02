<?php

namespace App\Nova\Metrics;

use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Value;

class TotalCollectionUsedValue extends Value
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
        $sets_used = $sets->sum('current_value_used');

        $bulk_brick = \App\Models\BulkBrick::all();
        $bulk_used = $bulk_brick->sum('value');

        $bricklink = \App\Models\BricklinkOrder::all();
        $bricklink_used = $bricklink->sum('total_cost');

        $result = new \Laravel\Nova\Metrics\ValueResult($sets_used + $bulk_used + $bricklink_used);
        return $result->prefix('$')->format('0,0.00');
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
        return 'total-collection-used-value';
    }
}
