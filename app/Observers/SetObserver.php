<?php

namespace App\Observers;

use App\Models\Set;
use NateJacobs\MurstenTrack\Resources\Set as BrickSetSearch;

class SetObserver
{
    /**
     * Handle the set "created" event.
     *
     * @param  \App\Set  $set
     * @return void
     */
    public function created(Set $set)
    {
        $sets = Set::where('catalog_item_id', $set->catalog_item_id)->get();

        $brickset = new BrickSetSearch();

        $brickset->setCollectionQuantity(
            $set->catalogItem->brickset_id,
            [
                'userHash' => getEnv('MURSTEN_TRACK_USER_HASH'),
                'qtyOwned' => $sets->count(),
            ]
        );
    }

    /**
     * Handle the set "deleted" event.
     *
     * @param  \App\Set  $set
     * @return void
     */
    public function deleted(Set $set)
    {
        $sets = Set::where('catalog_item_id', $set->catalog_item_id)->get();

        $brickset = new BrickSetSearch();

        $brickset->setCollectionQuantity(
            $set->catalogItem->brickset_id,
            [
                'userHash' => getEnv('MURSTEN_TRACK_USER_HASH'),
                'qtyOwned' => $sets->count(),
            ]
        );
    }
}
