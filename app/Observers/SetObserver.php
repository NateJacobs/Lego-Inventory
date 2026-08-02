<?php

namespace App\Observers;

use App\Models\Set;
use Illuminate\Support\Facades\Log;
use NateJacobs\MurstenTrack\Resources\Set as BrickSetSearch;
use Throwable;

class SetObserver
{
    public function created(Set $set): void
    {
        $this->syncOwnedQuantity($set);
    }

    public function deleted(Set $set): void
    {
        $this->syncOwnedQuantity($set);
    }

    public function restored(Set $set): void
    {
        $this->syncOwnedQuantity($set);
    }

    /**
     * Push the number of copies owned to the Brickset collection.
     *
     * This mirrors local state to a third-party service, so it is best-effort:
     * a set that has no Brickset id, a missing user hash, or an API that is
     * slow, down or rejecting us must not stop the copy being recorded here.
     * Previously any of those aborted the save outright.
     */
    protected function syncOwnedQuantity(Set $set): void
    {
        $bricksetId = $set->catalogItem?->brickset_id;

        if (empty($bricksetId)) {
            Log::info('Skipped Brickset quantity sync: no Brickset id.', [
                'set_id' => $set->id,
                'catalog_item_id' => $set->catalog_item_id,
            ]);

            return;
        }

        $userHash = config('services.brickset.user_hash');

        if (empty($userHash)) {
            Log::info('Skipped Brickset quantity sync: no user hash configured.', [
                'set_id' => $set->id,
            ]);

            return;
        }

        // Counted through the model, so trashed copies are already excluded.
        $quantity = Set::where('catalog_item_id', $set->catalog_item_id)->count();

        try {
            (new BrickSetSearch)->setCollectionQuantity($bricksetId, [
                'userHash' => $userHash,
                'qtyOwned' => $quantity,
            ]);
        } catch (Throwable $e) {
            Log::warning('Brickset quantity sync failed.', [
                'brickset_id' => $bricksetId,
                'catalog_item_id' => $set->catalog_item_id,
                'quantity' => $quantity,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
