<?php

namespace App\Providers;

use Laravel\Nova\Nova;
use Laravel\Nova\Cards\Help;
use Illuminate\Support\Facades\Gate;
use Laravel\Nova\NovaApplicationServiceProvider;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
        setlocale(LC_MONETARY, 'en_US.UTF-8');
    }

    /**
     * Register the Nova routes.
     *
     * @return void
     */
    protected function routes()
    {
        Nova::routes()
                ->withAuthenticationRoutes()
                ->withPasswordResetRoutes()
                ->register();
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     *
     * @return void
     */
    protected function gate()
    {
        Gate::define('viewNova', function ($user) {
            return in_array($user->email, [
                //
            ]);
        });
    }

    /**
     * Get the cards that should be displayed on the Nova dashboard.
     *
     * @return array
     */
    protected function cards()
    {
        return [
            // new Help,
            (new \App\Nova\Metrics\CollectionTrendByYear)->width('1/2'),
            (new \App\Nova\Metrics\TotalCollectionPiecePartition)->width('1/2'),
            (new \App\Nova\Metrics\TotalSets)->width('1/4'),
            (new \App\Nova\Metrics\TotalMinifigs)->width('1/4'),
            (new \App\Nova\Metrics\TotalCollectionUsedValue)->width('1/4'),
            (new \App\Nova\Metrics\TotalCollectionNewValue)->width('1/4'),
        ];
    }

    /**
     * Get the tools that should be listed in the Nova sidebar.
     *
     * @return array
     */
    public function tools()
    {
        return [];
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
