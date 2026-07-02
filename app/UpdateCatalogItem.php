<?php

namespace App;

use App\Exceptions\BricklinkPriceException;
use NateJacobs\MurstenStock\Client as BLClient;
use NateJacobs\MurstenStock\Resources\Price as BLPrice;

class UpdateCatalogItem
{
    public function __construct($model)
    {
        $this->model = $model;
    }

    public function updateBricklink()
    {
        $catalogItem = $this->model;

        if (is_null($catalogItem->bricklink_id)) {
            return;
        }

        $client = new BLClient();
        $client->setAuth([
        	'consumer_key' => getenv('MURSTEN_STOCK_CONSUMER_KEY'),
        	'consumer_secret' => getenv('MURSTEN_STOCK_CONSUMER_SECRET'),
        	'token' => getenv('MURSTEN_STOCK_TOKEN'),
        	'token_secret' => getenv('MURSTEN_STOCK_TOKEN_SECRET'),
        ]);

        $items = new BLPrice($client);

        $price_response_new = $this->fetchPrice($items, $catalogItem, 'N');
        $price_response_used = $this->fetchPrice($items, $catalogItem, 'U');

        // Parse the formatted currency strings (e.g. "$3,075.44") into numbers
        // by stripping everything except digits and the decimal point.
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
        $catalogItem->save();
    }

    /**
     * Fetch a BrickLink stock price for the given condition ("N" or "U").
     *
     * Throws on failure so the caller (the queued refresh job) can retry rather
     * than silently leaving the price unchanged.
     */
    protected function fetchPrice(BLPrice $items, $catalogItem, string $condition)
    {
        $response = $items->getPrice(
            $catalogItem->bricklink_id,
            $catalogItem->type,
            [
                'guide_type' => 'stock',
                'new_or_used' => $condition,
                'country_code' => 'US',
            ]
        );

        if ( $response instanceof \NateJacobs\MurstenStock\Exceptions\ResponseException) {
            throw new BricklinkPriceException(
                "BrickLink price lookup failed for {$catalogItem->bricklink_id}: ".$response->getMessage()
            );
        }

        return $response;
    }
}
