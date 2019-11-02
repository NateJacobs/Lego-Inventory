<?php

namespace App\Nova\Metrics;

use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Trend;
use Laravel\Nova\Metrics\TrendResult;
use Illuminate\Database\Eloquent\Builder;

class LugBulkPerYear extends Trend
{
    public $name = 'LUGBulk Pieces per Year';

    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function calculate(Request $request)
    {
        $lugbulk = \App\Models\BulkBrick::select('acquired_date', 'piece_count')
            ->whereHas('acquiredLocation', function (Builder $query) {
                $query->where('name', 'LUGBulk');
            })
            ->groupBy('acquired_date', 'piece_count')
            ->get();

        $lugbulk_grouped = $lugbulk->mapWithKeys(function($item, $key) {
            return [$item['acquired_date']->year => $item['piece_count']];
        });

        return (new TrendResult)
            ->trend($lugbulk_grouped->toArray())
            ->showLatestValue()
            ->format('0,0')
            ->suffix('pieces');
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
        return 'lug-bulk-per-year';
    }
}
