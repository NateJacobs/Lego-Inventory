<?php

namespace App\Nova\Metrics;

use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Partition;

class TotalCollectionPiecePartition extends Partition
{

    public function name()
    {
        return 'Collection Pieces by Category';
    }
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

        return $this->result([
            'Sets' => $sets_count,
            'Bulk Brick' => $bulk_count,
            'Bricklink' => $bricklink_count,
        ]);
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
        return 'total-collection-piece-partition';
    }
}
