<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Http\Requests\NovaRequest;

class Theme extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = 'App\Models\Theme';

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'name',
    ];

    /**
     * The column that should be ordered by.
     *
     * @var array
     */
    public static $orderBy = ['name' => 'asc'];

    public static function indexQuery(NovaRequest $request, $query)
    {
        return $query
            ->withCount('CatalogItems')
            ->whereNull('parent_id')
            ->withCount('Subthemes');
    }

    public static function detailQuery(NovaRequest $request, $query)
    {
        return $query->withCount('CatalogItems')->withCount('Subthemes');
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            Text::make('Name')->sortable(),
            Number::make('Unique Set Count', 'catalog_items_count')->sortable()->OnlyOnIndex(),
            Number::make('Subtheme Count', 'subthemes_count')->sortable()->OnlyOnIndex(),
            HasMany::make('Subtheme', 'Subthemes'),
            HasMany::make('Catalog Item', 'CatalogItems'),
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
