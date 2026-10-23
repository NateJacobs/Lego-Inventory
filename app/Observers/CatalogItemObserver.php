<?php

namespace App\Observers;

use App\Exceptions\BricksetImageException;
use App\Models\CatalogItem;
use App\Services\BricklinkClient;
use App\Services\BricksetClient;
use App\UpdateCatalogItem;
use Illuminate\Support\Facades\Log;
use NateJacobs\MurstenStock\Resources\Price as BLPrice;

class CatalogItemObserver
{
    /**
     * Handle the catalog item "creating" event.
     *
     * @param  \App\CatalogItem  $catalogItem
     * @return void
     */
    public function creating(CatalogItem $catalogItem)
    {
        $set = BricksetClient::findSet($catalogItem->item_number);
        $update = new UpdateCatalogItem($catalogItem);

        // Pass the response both times rather than calling updateDetails() and
        // updateImages(), which would each ask Brickset for this same set
        // again. Images come second, since their filenames are built from the
        // set numbers above.
        $update->applyBricksetDetails($set);

        try {
            $update->storeImages($set->images);
        } catch (BricksetImageException $e) {
            // Best effort, as with the prices below: a set is still worth
            // cataloguing without a picture, and collection:download-images
            // can fetch it later.
            Log::warning('Brickset image download failed for '.$catalogItem->item_number.': '.$e->getMessage());

            $catalogItem->image_path = '';
            $catalogItem->thumbnail_path = '';
        }

        $catalogItem = $this->getBricklinkPrices($catalogItem);
    }

    protected function getBricklinkPrices($catalogItem)
    {
        $items = new BLPrice(BricklinkClient::make());

        $price_response_new = $items->getPrice(
            $catalogItem->bricklink_lookup_number,
            $catalogItem->type,
            [
                'guide_type' => 'stock',
                'new_or_used' => 'N',
                'country_code' => 'US',
            ]
        );

        if ( $price_response_new instanceof \NateJacobs\MurstenStock\Exceptions\ResponseException) {
            // Best effort: the set is still worth cataloguing unpriced, and the
            // panel's Update BrickLink prices action can fill it in later.
            Log::warning('BrickLink price lookup failed for '.$catalogItem->bricklink_lookup_number.': '.$price_response_new->getMessage());

            return $catalogItem;
        } else {
            $price_response_used = $items->getPrice(
                $catalogItem->bricklink_lookup_number,
                $catalogItem->type,
                [
                    'guide_type' => 'stock',
                    'new_or_used' => 'U',
                    'country_code' => 'US',
                ]
            );

            // Parse the formatted currency strings (e.g. "$3,075.44") into
            // numbers by stripping everything except digits and the decimal
            // point. The previous approach chopped the first two characters off
            // any 6+ character price, which mangled every value with a
            // thousands separator ("$3,075.44" became "75.44").
            $new = (float) preg_replace('/[^0-9.]/', '', $price_response_new[0]->aggregatePrices['averagePrice']);
            $used = (float) preg_replace('/[^0-9.]/', '', $price_response_used[0]->aggregatePrices['averagePrice']);

            if ( 0.0 == $new && 0.0 == $used ) {
                $new = $catalogItem->retail_price;
                $used = $catalogItem->retail_price;
            } elseif ( 0.0 == $new ) {
                $new = $used;
            } elseif ( 0.0 == $used ) {
                $used = $new;
            }

            $catalogItem->bricklink_id = $catalogItem->item_number;
            $catalogItem->current_value_new = $new;
            $catalogItem->current_value_used = $used;
            $catalogItem->prices_updated_at = now();

            return $catalogItem;
        }
    }

}
