<?php

namespace App\Nova\Metrics;

use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Trend;
use Laravel\Nova\Metrics\TrendResult;

class CollectionTrendByYear extends Trend
{
    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function calculate(Request $request)
    {
        $collection = \App\Models\CollectionLog::all();

        $pieces_by_year = $collection->mapWithKeys(function($item, $key) {
            return [$item['date']->year => $item['piece_count']];
        });

        // $grouped = $collection->groupBy(function($item, $key) {
        //     return $item['date']->year;
        // });
        //
        // $pieces_by_year = $grouped->map(function($item, $key) {
        //     $new = $item->filter(function($log) use ($item) {
        //         return $log->date->equalTo($item->max->date);
        //     });
        //
        //     return $new->mapWithKeys(function($item, $key) {
        //         return [$key => $item['piece_count']];
        //     })->first();
        // });

        return (new TrendResult)
            ->trend($pieces_by_year->toArray())
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
        return now()->addMinutes(15);
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'collection-trend-by-year';
    }
}
