<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Currency;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Markdown;
use Laravel\Nova\Fields\Date;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;

class BulkBrick extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = 'App\Models\BulkBrick';

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'notes';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'notes',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            Number::make('Pieces', 'piece_count'),
            Currency::make('Cost')->format('%.2n'),
            Currency::make('Value')->format('%.2n'),
            Date::make('Date', 'acquired_date')->format('MMMM DD, YYYY'),
            Markdown::make('Notes')->alwaysShow(),
            BelongsTo::make('Acquired Location', 'AcquiredLocation')->sortable(),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [
            new Metrics\TotalBulkBrickPieceCount,
            new Metrics\TotalBulkBrickValue,
            new Metrics\TotalBulkBrickCost,
            (new Metrics\BulkBrickPieceCount)->width('1/4'),
            (new Metrics\BulkBrickValue)->width('1/4'),
            (new Metrics\BulkBrickCost)->width('1/4'),
            (new Metrics\LugBulkPerYear)->width('1/4'),
        ];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }
}
