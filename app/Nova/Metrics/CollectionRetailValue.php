<?php

namespace App\Nova\Metrics;

use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Value;

class CollectionRetailValue extends Value
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
        $sets_retail = $sets->sum('retail_price');

        $bulk_brick = \App\Models\BulkBrick::all();
        $bulk_retail = $bulk_brick->sum('value');

        $bricklink = \App\Models\BricklinkOrder::all();
        $bricklink_retail = $bricklink->sum('total_cost');

        $total = $sets_retail + $bulk_retail + $bricklink_retail;

        $result = new \Laravel\Nova\Metrics\ValueResult($total);
        return $result->currency('$');
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
        // return now()->addMinutes(5);
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'collection-retail-value';
    }
}
