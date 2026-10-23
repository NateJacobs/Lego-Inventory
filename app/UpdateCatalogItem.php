<?php

namespace App;

use App\Exceptions\BricklinkPriceException;
use App\Exceptions\BricksetImageException;
use App\Models\CatalogItem;
use App\Models\Theme;
use App\Services\BricklinkClient;
use App\Services\BricksetClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use NateJacobs\MurstenStock\Resources\Price as BLPrice;
use NateJacobs\MurstenTrack\Responses\Set as BrickSetResponse;

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
        // Both lookups came back, so the pair of values above is as of now.
        $catalogItem->prices_updated_at = now();
        $catalogItem->save();
    }

    /**
     * Fetch this set from Brickset again and take its description from what
     * comes back: name, year, piece and minifigure counts, MSRP, theme and
     * Brickset url.
     *
     * Brickset is the source for all of it, so Brickset wins — a set renamed
     * or re-themed there, or given a piece count it was missing, is corrected
     * here. The pictures are left to updateImages() and the values to
     * updateBricklink(); bricklink_id and the item type are this
     * application's own and are never touched.
     *
     * @throws \App\Exceptions\BricksetLookupException
     */
    public function updateDetails(): void
    {
        $catalogItem = $this->model;

        $this->applyBricksetDetails(BricksetClient::findSet($catalogItem->item_number));

        $catalogItem->save();
    }

    /**
     * Copy a Brickset response onto the item without saving it.
     *
     * Takes the response rather than looking the set up, so the observer can
     * pass the one it already holds instead of asking Brickset twice.
     */
    public function applyBricksetDetails(BrickSetResponse $set): void
    {
        $catalogItem = $this->model;

        $catalogItem->brickset_id = (int) $set->itemNumbers['setID'];
        $catalogItem->set_number = $set->itemNumbers['number'];
        $catalogItem->set_number_variant = (int) $set->itemNumbers['numberVariant'];
        $catalogItem->name = $set->name;
        $catalogItem->piece_count = (int) $set->pieces;
        $catalogItem->minifig_count = empty($set->minifigs) ? 0 : $set->minifigs;
        $catalogItem->retail_price = empty($set->prices['US']['retailPrice']) ? 0 : $set->prices['US']['retailPrice'];
        $catalogItem->year = $set->year;
        $catalogItem->theme_id = Theme::resolveFromBrickset($set->themeDetails);
        $catalogItem->theme_group = $set->themeDetails['themeGroup'];
        $catalogItem->brickset_url = $set->bricksetURL;
    }

    /**
     * Put this item's pictures on the public disk, fetching the set from
     * Brickset to find out where they live.
     *
     * Returns false when the files are already there, so a backfill across the
     * whole catalogue can be re-run without paying for what it already has.
     *
     * @throws \App\Exceptions\BricksetLookupException|BricksetImageException
     */
    public function updateImages(bool $force = false): bool
    {
        $catalogItem = $this->model;

        if (! $force && $catalogItem->hasStoredImages()) {
            return false;
        }

        $this->storeImages(BricksetClient::findSet($catalogItem->item_number)->images);

        $catalogItem->save();

        return true;
    }

    /**
     * Download the pictures a Brickset response points at and point the item's
     * columns at the stored copies.
     *
     * Takes the images array rather than looking the set up, so the observer
     * can pass the response it already holds instead of asking Brickset twice
     * for the same set.
     *
     * @param  array<string, string>  $images  As built by MurstenTrack's Set response.
     *
     * @throws BricksetImageException
     */
    public function storeImages(array $images): void
    {
        $catalogItem = $this->model;
        $url = $images['imageURL'] ?? '';

        if (blank($url)) {
            throw new BricksetImageException(
                "Brickset has no picture for set {$catalogItem->item_number}."
            );
        }

        $disk = Storage::disk('public');

        $imagePath = $this->pathFor('full', $url);
        $disk->put($imagePath, $this->fetchImage($url));

        // Most sets have a separate thumbnail; where one is missing, both
        // columns point at the full picture so the tables still show something.
        $thumbnailUrl = $images['thumbnailURL'] ?? '';
        $thumbnailPath = $imagePath;

        if (filled($thumbnailUrl)) {
            $thumbnailPath = $this->pathFor('thumb', $thumbnailUrl);
            $disk->put($thumbnailPath, $this->fetchImage($thumbnailUrl));
        }

        $catalogItem->image_path = $imagePath;
        $catalogItem->thumbnail_path = $thumbnailPath;
    }

    protected function pathFor(string $prefix, string $url): string
    {
        return 'set-images/'.$prefix.'-'.$this->model->item_number.'.'.$this->extensionOf($url);
    }

    /**
     * Brickset serves mostly .jpg but not only — two rows here were imported as
     * .jpeg — so take the extension from the URL rather than assuming one.
     */
    protected function extensionOf(string $url): string
    {
        $extension = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)
            ? $extension
            : 'jpg';
    }

    /**
     * The old code used file_get_contents(), which returns false on an HTTP
     * error and so stored an empty file without anyone noticing.
     *
     * @throws BricksetImageException
     */
    protected function fetchImage(string $url): string
    {
        $response = Http::timeout(20)->retry(2, 500, throw: false)->get($url);

        if ($response->failed() || $response->body() === '') {
            throw new BricksetImageException(
                "Could not download {$url}: ".($response->failed()
                    ? 'HTTP '.$response->status().'.'
                    : 'the response was empty.')
            );
        }

        return $response->body();
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
