<?php

namespace App\Services;

use App\Models\Set;
use Illuminate\Support\Facades\Cache;

/**
 * The handful of figures the public landing page puts on show.
 *
 * Kept out of the view, and cached, because the page is the one part of this
 * application a signed-out visitor can reach: it should not run half a dozen
 * aggregates over every owned set on each hit. An hour is far shorter than the
 * interval at which any of these actually move.
 */
class CollectionSummary
{
    public const CACHE_KEY = 'collection-summary';

    /**
     * Pieces come from CollectionValuation rather than being counted here, so
     * the landing page and the monthly snapshots cannot disagree about how big
     * the collection is.
     */
    public function __construct(protected CollectionValuation $valuation)
    {
    }

    /**
     * @return array<string, int|null>
     */
    public function figures(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addHour(), fn (): array => [
            'copies' => $this->copies(),
            'sets' => $this->distinctSets(),
            // Every piece owned: the catalogued sets, the bulk lots and the
            // BrickLink orders. Counting only the sets left a third of them
            // out.
            'pieces' => $this->valuation->pieceCount(),
            // Sets are the only thing here that records minifigures; a bulk
            // lot and an order are both just a number of pieces.
            'minifigures' => $this->setsSum('minifig_count'),
            'themes' => $this->topLevelThemes(),
            'earliest_set' => $this->setYear('min'),
            'latest_set' => $this->setYear('max'),
            'collecting_since' => $this->collectingSince(),
        ]);
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Every owned copy. Owning three of a set counts three times here.
     */
    protected function copies(): int
    {
        return Set::query()->count();
    }

    /**
     * Distinct sets owned, however many copies of each.
     */
    protected function distinctSets(): int
    {
        return Set::query()->distinct()->count('catalog_item_id');
    }

    /**
     * Sum a catalog_items column across every owned copy.
     */
    protected function setsSum(string $column): int
    {
        return (int) Set::query()
            ->join('catalog_items', 'sets.catalog_item_id', '=', 'catalog_items.id')
            ->sum("catalog_items.{$column}");
    }

    /**
     * Top-level themes represented. A set records its most specific theme, so
     * count the parent where it has one — otherwise "Harry Potter" would be
     * counted thirteen times over, once per subtheme.
     */
    protected function topLevelThemes(): int
    {
        return Set::query()
            ->join('catalog_items', 'sets.catalog_item_id', '=', 'catalog_items.id')
            ->join('themes', 'catalog_items.theme_id', '=', 'themes.id')
            ->distinct()
            ->count(Set::raw('coalesce(themes.parent_id, themes.id)'));
    }

    /**
     * The year of the oldest or newest set owned. Year zero is how this
     * database spells "Brickset did not say".
     */
    protected function setYear(string $direction): ?int
    {
        $year = Set::query()
            ->join('catalog_items', 'sets.catalog_item_id', '=', 'catalog_items.id')
            ->where('catalog_items.year', '>', 0)
            ->{$direction}('catalog_items.year');

        return $year ? (int) $year : null;
    }

    protected function collectingSince(): ?int
    {
        $date = Set::query()->whereNotNull('purchase_date')->min('purchase_date');

        return $date ? (int) substr((string) $date, 0, 4) : null;
    }
}
