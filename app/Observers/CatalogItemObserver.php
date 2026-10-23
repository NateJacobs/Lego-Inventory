<?php

namespace App\Observers;

use App\Exceptions\BricksetImageException;
use App\Models\CatalogItem;
use App\Models\Theme;
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

        $catalogItem->brickset_id = (int) $set->itemNumbers['setID'];
        $catalogItem->set_number = $set->itemNumbers['number'];
        $catalogItem->set_number_variant = (int) $set->itemNumbers['numberVariant'];
        $catalogItem->name = $set->name;
        $catalogItem->piece_count = (int) $set->pieces;
        $catalogItem->minifig_count = empty($set->minifigs) ? 0 : $set->minifigs;
        $catalogItem->retail_price = empty($set->prices['US']['retailPrice']) ? 0 : $set->prices['US']['retailPrice'];
        $catalogItem->year = $set->year;
        $catalogItem->theme_id = $this->resolveTheme($set->themeDetails);
        $catalogItem->theme_group = $set->themeDetails['themeGroup'];
        $catalogItem->brickset_url = $set->bricksetURL;

        // Pass the response we already hold rather than updateImages(), which
        // would ask Brickset for this same set a second time. Set after the
        // numbers above, since the filenames are built from them.
        try {
            (new UpdateCatalogItem($catalogItem))->storeImages($set->images);
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

    /**
     * The id of the most specific theme Brickset gives for a set: its subtheme
     * when there is one, otherwise its top-level theme.
     *
     * Brickset reuses subtheme names — "General", "Miscellaneous",
     * "Promotional" all appear under many themes — so a subtheme is only ever
     * matched, or created, beneath its own parent.
     */
    private function resolveTheme(array $themeDetails): ?int
    {
        $themeId = $this->findOrCreateTheme($themeDetails['theme'] ?? null, null);

        if ($themeId === null) {
            return null;
        }

        return $this->findOrCreateTheme($themeDetails['subtheme'] ?? null, $themeId) ?? $themeId;
    }

    private function findOrCreateTheme(?string $name, ?int $parentId): ?int
    {
        if (empty($name)) {
            return null;
        }

        return Theme::firstOrCreate([
            'name' => $name,
            'parent_id' => $parentId,
        ])->id;
    }
}
