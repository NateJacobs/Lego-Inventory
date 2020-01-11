<?php

namespace App;

use Illuminate\Support\Facades\Log;
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

        if (false === is_null($catalogItem->bricklink_id)) {
            $client = new BLClient();
            $client->setAuth([
            	'consumer_key' => getenv('MURSTEN_STOCK_CONSUMER_KEY'),
            	'consumer_secret' => getenv('MURSTEN_STOCK_CONSUMER_SECRET'),
            	'token' => getenv('MURSTEN_STOCK_TOKEN'),
            	'token_secret' => getenv('MURSTEN_STOCK_TOKEN_SECRET'),
            ]);

            $items = new BLPrice($client);

            $price_response_new = $items->getPrice(
                $catalogItem->bricklink_id,
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
                    $catalogItem->bricklink_id,
                    $catalogItem->type,
                    [
                        'guide_type' => 'stock',
                        'new_or_used' => 'U',
                        'country_code' => 'US',
                    ]
                );

                $new = trim(str_replace('$', '', $price_response_new[0]->aggregatePrices['averagePrice']));
                $used = trim(str_replace('$', '', $price_response_used[0]->aggregatePrices['averagePrice']));

                if ( strlen($new) >= 6 ) {
                    $new = trim(substr($new, 2));
                } elseif ( strlen($new) <= 5 ) {
                    $new = trim($new);
                }

                if ( strlen($used) >= 6 ) {
                    $used = trim(substr($used, 2));
                } elseif ( strlen($used) <= 5 ) {
                    $used = trim($used);
                }

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
                $catalogItem->save();
            }
        }
    }
}
