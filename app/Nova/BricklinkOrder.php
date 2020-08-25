<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Markdown;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\HasOne;
use Laravel\Nova\Fields\Currency;
use Laravel\Nova\Fields\KeyValue;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;

class BricklinkOrder extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = 'App\Models\BricklinkOrder';

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
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
            Date::make('Purchase Date')->format('MMMM DD, YYYY')->sortable()->rules('required'),
            Text::make('Seller Name')->sortable()->rules('required'),
            Text::make('Store Name')->sortable()->rules('required'),
            Number::make('Order Number')->sortable()->rules('required'),
            Number::make('Pieces')->sortable()->rules('required'),
            Currency::make('Order Cost')
                ->sortable()
                ->rules('required')
                ->hideFromIndex(),
            Currency::make('Shipping Cost')
                ->rules('required')
                ->hideFromIndex(),
            Currency::make('Total Cost')
                ->readOnly(),
            Markdown::make('Notes')->alwaysShow(),
            KeyValue::make('Details')->rules('json'),
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
            new Metrics\TotalBLOrders,
            new Metrics\TotalBLValue,
            new Metrics\TotalBLPieces,
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
