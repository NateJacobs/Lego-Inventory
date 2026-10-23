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

        // bricklink_id is an override where BrickLink numbers an item
        // differently, and empty on anything never priced — so fall back to the
        // Brickset number rather than asking for /items///price, which
        // BrickLink rejects as a malformed URI. The old guard used is_null(),
        // which an empty string is not.
        if (blank($catalogItem->bricklink_lookup_number) || blank($catalogItem->type)) {
            throw new BricklinkPriceException(
                'Cannot price catalog item '.$catalogItem->id.': it has no '
                .(blank($catalogItem->bricklink_lookup_number) ? 'set number' : 'item type').'.'
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

        if (blank($catalogItem->bricklink_id)) {
            $catalogItem->bricklink_id = $catalogItem->item_number;
        }

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
            $catalogItem->bricklink_lookup_number,
            $catalogItem->type,
            [
                'guide_type' => 'stock',
                'new_or_used' => $condition,
                'country_code' => 'US',
            ]
        );

        if ( $response instanceof \NateJacobs\MurstenStock\Exceptions\ResponseException) {
            throw new BricklinkPriceException(
                "BrickLink price lookup failed for {$catalogItem->bricklink_lookup_number}: ".$response->getMessage()
            );
        }

        return $response;
    }
}
