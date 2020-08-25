<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Avatar;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Markdown;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Currency;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;

class Set extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = 'App\Models\Set';

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
        'current_condition',
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
            Avatar::make('Image', 'CatalogItem.thumbnail_path')->exceptOnForms()->hideFromDetail(),
            BelongsTo::make('Set', 'CatalogItem', 'App\Nova\CatalogItem')->searchable()->sortable(),
            Currency::make('Retail Price', 'CatalogItem.retail_price')
                ->exceptOnForms(),
            Date::make('Purchase Date')->format('MMMM DD, YYYY')->sortable(),
            Currency::make('Purchase Price'),
            Text::make('Savings', function() {
                if (
                    (isset($this->CatalogItem->retail_price) && $this->CatalogItem->retail_price > 0)
                    && isset($this->purchase_price)
                ) {
                    $diff = $this->CatalogItem->retail_price - $this->purchase_price;

                    return round(($diff / $this->CatalogItem->retail_price) * 100, 2).'%';
                }
            })->exceptOnForms()->hideFromIndex(),
            Select::make('Current Condition')->options([
                'parted out' => 'Parted Out',
                'assembled' => 'Assembled',
                'misb' => 'MISB',
            ])->displayUsingLabels()->rules('required'),
            BelongsTo::make('Storage Location', 'StorageLocation')->sortable(),
            BelongsTo::make('Acquired From', 'AcquiredLocation', '\App\Nova\AcquiredLocation')->sortable(),
            Image::make('Image', 'CatalogItem.image_path')->exceptOnForms()->hideFromIndex(),
            Markdown::make('Notes')->alwaysShow(),
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
            (new Metrics\TotalUniqueSets)->width('1/4'),
            (new Metrics\TotalSets)->width('1/4'),
            (new Metrics\TotalPieces)->width('1/4'),
            (new Metrics\TotalMinifigs)->width('1/4'),
            new Metrics\TotalNewValue,
            new Metrics\TotalUsedValue,
            new Metrics\TotalRetailValue,
            (new Metrics\SetCount)->width('1/4'),
            (new Metrics\PurchasePrice)->width('1/4'),
            (new Metrics\NewValue)->width('1/4'),
            (new Metrics\UsedValue)->width('1/4'),
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
