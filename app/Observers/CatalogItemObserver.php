<?php

namespace App\Observers;

use App\Exceptions\BricksetLookupException;
use App\Models\CatalogItem;
use App\Models\Theme;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use NateJacobs\MurstenStock\Client as BLClient;
use NateJacobs\MurstenStock\Resources\Price as BLPrice;
use NateJacobs\MurstenTrack\Resources\Set as BrickSetSearch;

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
        // query Brickset for the set
        $brickset = new BrickSetSearch();

        $set_response = $brickset->getSets(
            [
                'setNumber' => $catalogItem->set_number.'-'.$catalogItem->set_number_variant
            ]
        );

        // getSets returns a valid array only when a set is found; on failure it
        // returns a ResponseException, an ErrorException, or null. Fail with a
        // clear message rather than treating that result as an array (and rather
        // than later hitting a NOT NULL error because no fields got populated).
        if (! is_array($set_response) || ! isset($set_response[0])) {
            $reason = $set_response instanceof \Throwable
                ? $set_response->getMessage()
                : 'no matching set was found';

            $setNumber = $catalogItem->set_number.'-'.$catalogItem->set_number_variant;

            throw new BricksetLookupException("Could not load set {$setNumber} from Brickset: {$reason}");
        }

        $set = $set_response[0];

        // save images
        if (isset($set->images['imageURL']) && ! empty($set->images['imageURL'])) {
            $name = $set->itemNumbers['number'].'-'.(int) $set->itemNumbers['numberVariant'];

            $full_image = file_get_contents($set->images['imageURL']);
            $full_image_path = 'set-images/full-'.$name.'.jpg';
            Storage::disk('public')->put($full_image_path, $full_image);

            $thumbnail_image = file_get_contents($set->images['thumbnailURL']);
            $thumbnail_image_path = 'set-images/thumb-'.$name.'.jpg';
            Storage::disk('public')->put($thumbnail_image_path, $thumbnail_image);
        } else {
            $full_image_path = '';
            $thumbnail_image_path = '';
        }

        $catalogItem->brickset_id = (int) $set->itemNumbers['setID'];
        $catalogItem->set_number = $set->itemNumbers['number'];
        $catalogItem->set_number_variant = (int) $set->itemNumbers['numberVariant'];
        $catalogItem->name = $set->name;
        $catalogItem->piece_count = (int) $set->pieces;
        $catalogItem->minifig_count = empty($set->minifigs) ? 0 : $set->minifigs;
        $catalogItem->retail_price = empty($set->prices['US']['retailPrice']) ? 0 : $set->prices['US']['retailPrice'];
        $catalogItem->year = $set->year;
        $catalogItem->theme_id = $this->getTheme($set->themeDetails['theme']);
        $catalogItem->subtheme_id = $this->getSubTheme($set->themeDetails['subtheme'], $catalogItem->theme_id);
        $catalogItem->theme_group = $set->themeDetails['themeGroup'];
        $catalogItem->image_path = $full_image_path;
        $catalogItem->thumbnail_path = $thumbnail_image_path;
        $catalogItem->brickset_url = $set->bricksetURL;

        $catalogItem = $this->getBricklinkPrices($catalogItem);
    }

    protected function getBricklinkPrices($catalogItem)
    {
        $client = new BLClient();
        $client->setAuth([
        	'consumer_key' => getenv('MURSTEN_STOCK_CONSUMER_KEY'),
        	'consumer_secret' => getenv('MURSTEN_STOCK_CONSUMER_SECRET'),
        	'token' => getenv('MURSTEN_STOCK_TOKEN'),
        	'token_secret' => getenv('MURSTEN_STOCK_TOKEN_SECRET'),
        ]);

        $items = new BLPrice($client);

        $price_response_new = $items->getPrice(
            $catalogItem->set_number.'-'.$catalogItem->set_number_variant,
            $catalogItem->type,
            [
                'guide_type' => 'stock',
                'new_or_used' => 'N',
                'country_code' => 'US',
            ]
        );

        if ( $price_response_new instanceof \NateJacobs\MurstenStock\Exceptions\ResponseException) {
            Log::warning('BrickLink price lookup failed for '.$catalogItem->set_number.'-'.$catalogItem->set_number_variant.': '.$price_response_new->getMessage());
        } else {
            $price_response_used = $items->getPrice(
                $catalogItem->set_number.'-'.$catalogItem->set_number_variant,
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

            $catalogItem->bricklink_id = $catalogItem->set_number.'-'.$catalogItem->set_number_variant;
            $catalogItem->current_value_new = $new;
            $catalogItem->current_value_used = $used;

            return $catalogItem;
        }
    }

    private function getTheme($theme_name)
    {
        if (empty($theme_name)) {
            return null;
        }

        // Match an existing top-level theme by name, or create it.
        return Theme::firstOrCreate([
            'name' => $theme_name,
            'parent_id' => null,
        ])->id;
    }

    private function getSubTheme($subtheme_name, $theme_id)
    {
        // 0 signals "no subtheme": a set without a subtheme, or one whose
        // parent theme could not be resolved.
        if (empty($subtheme_name) || empty($theme_id)) {
            return 0;
        }

        // Match an existing subtheme under this theme by name, or create it.
        return Theme::firstOrCreate([
            'name' => $subtheme_name,
            'parent_id' => $theme_id,
        ])->id;
    }
}
