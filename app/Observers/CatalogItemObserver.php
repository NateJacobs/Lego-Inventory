<?php

namespace App\Observers;

use App\Models\CatalogItem;
use App\Models\Theme;
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

        if ($set_response instanceof \NateJacobs\MurstenTrack\Exceptions\ResponseException) {
        	// do nothing
        } else {
        	// save images
        	if ('true' === $set_response[0]->images['image']) {
        		$name = $set_response[0]->images['imageFilename'];

        		$full_image = file_get_contents($set_response[0]->images['imageURL']);
                $full_image_path = 'set-images/full-'.$name.'.jpg';
        		Storage::disk('public')->put($full_image_path, $full_image);

        		$thumbnail_image = file_get_contents($set_response[0]->images['thumbnailURL']);
                $thumbnail_image_path = 'set-images/thumb-'.$name.'.jpg';
        		Storage::disk('public')->put($thumbnail_image_path, $thumbnail_image);
        	} else {
        		$full_image_path = '';
        		$thumbnail_image_path = '';
        	}

    		$catalogItem->brickset_id = (int) $set_response[0]->itemNumbers['setID'];
    		$catalogItem->set_number = $set_response[0]->itemNumbers['number'];
    		$catalogItem->set_number_variant = (int) $set_response[0]->itemNumbers['numberVariant'];
    		$catalogItem->name = $set_response[0]->name;
    		$catalogItem->piece_count = (int) $set_response[0]->pieces;
    		$catalogItem->minifig_count = empty($set_response[0]->minifigs) ? 0 : $set_response[0]->minifigs;
    		$catalogItem->retail_price = empty($set_response[0]->prices['USRetailPrice']) ? 0 : $set_response[0]->prices['USRetailPrice'];
    		$catalogItem->year = $set_response[0]->year;
    		$catalogItem->theme_id = $this->getTheme($set_response[0]->themeDetails['theme']);
    		$catalogItem->subtheme_id = $this->getSubTheme($set_response[0]->themeDetails['subtheme'], $catalogItem->theme_id);
    		$catalogItem->theme_group = $set_response[0]->themeDetails['themeGroup'];
    		$catalogItem->image_path = $full_image_path;
    		$catalogItem->thumbnail_path = $thumbnail_image_path;
    		$catalogItem->brickset_url = $set_response[0]->bricksetURL;
    	}

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
            // it is an error
            // do nothing
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

            $new = trim(substr($price_response_new[0]->aggregatePrices['averagePrice'], 3));
            $used = trim(substr($price_response_used[0]->aggregatePrices['averagePrice'], 3));

            if ( '0.00' == $new && '0.00' == $used ) {
                $new = $catalogItem->retail_price;
                $used = $catalogItem->retail_price;
            } elseif ( '0.00' == $new ) {
                $new = $used;
            } elseif ( '0.00' == $used ) {
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
        $theme = Theme::select('id')->where('name', $theme_name)->whereNull('parent_id')->first();

        return $theme->id;
    }

    private function getSubTheme($subtheme_name, $theme_id)
    {
        $subtheme = Theme::select('id')->where('parent_id', $theme_id)->where('name', $subtheme_name)->first();

        if (is_null($subtheme)) {
			return 0;
		} else {
			return $subtheme->id;
		}
    }
}
