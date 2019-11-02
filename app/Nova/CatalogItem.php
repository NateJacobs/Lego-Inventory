<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Avatar;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Currency;
use Inspheric\Fields\Url;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;

class CatalogItem extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = 'App\Models\CatalogItem';

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public function title()
    {
        return $this->set_number.' '.$this->name;
    }

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'set_number',
        'name',
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
            Avatar::make('Image', 'thumbnail_path')->prunable()->onlyOnIndex(),
            Text::make('Set Number')->sortable()->rules('required'),
            Number::make('Variant', 'set_number_variant')->rules('required')->onlyOnForms(),
            Select::make('Type')->options([
                'set' => 'Set',
                'book' => 'Book',
                'gear' => 'Gear',
            ])->displayUsingLabels()->rules('required')->onlyOnForms(),
            Text::make('Name')->hideWhenCreating(),
            Number::make('Year')->sortable()->hideWhenCreating(),
            BelongsTo::make('Theme')->sortable()->hideWhenCreating(),
            BelongsTo::make('Subtheme')->sortable()->hideFromIndex()->hideWhenCreating(),
            Number::make('Pieces', 'piece_count')->sortable()->hideWhenCreating(),
            Number::make('Minifigs', 'minifig_count')->sortable()->hideWhenCreating(),
            Currency::make('MSRP', 'retail_price')->format('%.2n')->sortable()->hideWhenCreating(),
            Currency::make('Current New Price', 'current_value_new')->format('%.2n')->onlyOnDetail()->sortable()->hideWhenCreating(),
            Currency::make('Current Used Price', 'current_value_used')->format('%.2n')->onlyOnDetail()->sortable()->hideWhenCreating(),
            Number::make('Total Sets', 'sets_count')->sortable()->exceptOnForms(),
            Currency::make('Total Value', function() {
                return money_format('%.2n', $this->sets_count * $this->retail_price);
            }),
            Url::make('Brickset URL')->domainLabel()->clickable()->onlyOnDetail(),
            Image::make('Image', 'image_path')->prunable()->maxWidth(500)->hideFromIndex(),
            HasMany::make('Sets'),
        ];
    }

    public static function indexQuery(NovaRequest $request, $query)
    {
        return $query->withCount('sets');
    }

    public static function detailQuery(NovaRequest $request, $query)
    {
        return $query->withCount('sets');
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
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
