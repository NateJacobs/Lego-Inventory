<?php

namespace App;

use App\Exceptions\BricklinkPriceException;
use App\Models\CatalogItem;
use App\Services\BricklinkClient;
use NateJacobs\MurstenStock\Resources\Price as BLPrice;

class UpdateCatalogItem
{
    // Declared rather than assigned on the fly: PHP 8.2 deprecated dynamic
    // properties, and this ran on 8.4.
    public function __construct(protected CatalogItem $model) {}

    public function updateBricklink()
    {
        $catalogItem = $this->model;

        // Ask by set number rather than bricklink_id: that column is only
        // written once a lookup has succeeded, so it is empty on anything never
        // priced — and an empty one asked BrickLink for /items///price, which
        // it rejects as a malformed URI. is_null() missed it because the column
        // defaults to an empty string rather than null.
        if (blank($catalogItem->set_number) || blank($catalogItem->type)) {
            throw new BricklinkPriceException(
                'Cannot price catalog item '.$catalogItem->id.': it has no '
                .(blank($catalogItem->set_number) ? 'set number' : 'item type').'.'
            );
        }

        $items = new BLPrice(BricklinkClient::make());

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

        $catalogItem->bricklink_id = $catalogItem->item_number;
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
            $catalogItem->item_number,
            $catalogItem->type,
            [
                'guide_type' => 'stock',
                'new_or_used' => $condition,
                'country_code' => 'US',
            ]
        );

        if ( $response instanceof \NateJacobs\MurstenStock\Exceptions\ResponseException) {
            throw new BricklinkPriceException(
                "BrickLink price lookup failed for {$catalogItem->item_number}: ".$response->getMessage()
            );
        }

        return $response;
    }
}
