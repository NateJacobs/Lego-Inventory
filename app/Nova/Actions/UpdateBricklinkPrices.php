<?php

namespace App\Nova\Actions;

use App\Exceptions\BricklinkPriceException;
use App\UpdateCatalogItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Http\Requests\NovaRequest;

class UpdateBricklinkPrices extends Action
{
    use InteractsWithQueue, Queueable;

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        $updated = 0;
        $failures = [];

        foreach ($models as $model) {
            try {
                (new UpdateCatalogItem($model))->updateBricklink();
                $updated++;
            } catch (BricklinkPriceException $e) {
                $failures[] = $model->bricklink_id ?? $model->set_number;
            }
        }

        if (! empty($failures)) {
            return Action::danger("Updated {$updated}. BrickLink lookup failed for: ".implode(', ', $failures));
        }

        return Action::message("Updated BrickLink prices for {$updated} set(s).");
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [];
    }
}
