<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Currency;
use Laravel\Nova\Fields\Markdown;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;

class CollectionLog extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = 'App\Models\CollectionLog';

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'date';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'date',
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
            Date::make('Date')->format('MMMM DD, YYYY'),
            Number::make('Total Sets'),
            Number::make('Piece Count'),
            Currency::make('Retail Value')->format('%.2n'),
            Currency::make('Used Value')->format('%.2n'),
            Currency::make('New Value')->format('%.2n'),
            Markdown::make('Notes'),
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
            new Metrics\TotalCollectionNewValue,
            new Metrics\TotalCollectionUsedValue,
            new Metrics\CollectionRetailValue,
            new Metrics\TotalCollectionPieces,
            new Metrics\TotalSets,
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
