<?php

namespace App\Services;

use App\Models\BricklinkOrder;
use App\Models\BulkBrick;
use App\Models\CatalogItem;
use App\Models\Set;

/**
 * Single source of truth for the value of the collection.
 *
 * The collection is made up of three segments: owned sets (one row per owned
 * copy, valued from its catalog item), bulk brick lots, and BrickLink orders.
 * Every figure below is shared by the Nova dashboard metrics and the periodic
 * CollectionLog snapshot so they can never drift apart.
 */
class CollectionValuation
{
    /**
     * Total value if every set were valued at its current "new" BrickLink price.
     */
    public function newValue(): float
    {
        return round($this->ownedSetsSum('current_value_new') + $this->bulkValue() + $this->orderValue(), 2);
    }

    /**
     * Total value if every set were valued at its current "used" BrickLink price.
     */
    public function usedValue(): float
    {
        return round($this->ownedSetsSum('current_value_used') + $this->bulkValue() + $this->orderValue(), 2);
    }

    /**
     * Total value at original retail (MSRP).
     */
    public function retailValue(): float
    {
        return round($this->ownedSetsSum('retail_price') + $this->bulkValue() + $this->orderValue(), 2);
    }

    /**
     * Total number of pieces across the whole collection.
     */
    public function pieceCount(): int
    {
        return (int) ($this->ownedSetsSum('piece_count')
            + BulkBrick::all()->sum('piece_count')
            + BricklinkOrder::all()->sum('pieces'));
    }

    /**
     * Number of owned sets (counting every copy).
     */
    public function totalSets(): int
    {
        return Set::count();
    }

    /**
     * Piece counts broken down by collection segment.
     *
     * @return array<string, int>
     */
    public function pieceCountBreakdown(): array
    {
        return [
            'Sets' => (int) $this->ownedSetsSum('piece_count'),
            'Bulk Brick' => (int) BulkBrick::all()->sum('piece_count'),
            'Bricklink' => (int) BricklinkOrder::all()->sum('pieces'),
        ];
    }

    /**
     * The payload persisted to a collection_logs snapshot row.
     *
     * @return array<string, float|int>
     */
    public function toArray(): array
    {
        return [
            'new_value' => $this->newValue(),
            'used_value' => $this->usedValue(),
            'retail_value' => $this->retailValue(),
            'piece_count' => $this->pieceCount(),
            'total_sets' => $this->totalSets(),
        ];
    }

    /**
     * Sum a catalog_items column across every owned set (one row per owned copy).
     */
    protected function ownedSetsSum(string $column): float
    {
        return (float) CatalogItem::join('sets', 'catalog_items.id', '=', 'sets.catalog_item_id')
            ->sum('catalog_items.'.$column);
    }

    protected function bulkValue(): float
    {
        return (float) BulkBrick::all()->sum('value');
    }

    protected function orderValue(): float
    {
        return (float) BricklinkOrder::all()->sum('total_cost');
    }
}
