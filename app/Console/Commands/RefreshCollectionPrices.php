<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\WorksWithoutAQueue;
use App\Exceptions\BricklinkPriceException;
use App\Jobs\RefreshCatalogItemPrice;
use App\Models\CatalogItem;
use App\UpdateCatalogItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class RefreshCollectionPrices extends Command
{
    use WorksWithoutAQueue;

    /**
     * Seconds between sets when running without a worker. Each one is two
     * BrickLink calls, so this matches the 30-a-minute 'bricklink' limiter the
     * queued path would have applied.
     */
    protected const PAUSE_SECONDS = 2;

    /**
     * @var string
     */
    protected $signature = 'collection:refresh-prices
                            {--id=* : Limit to these catalog item ids}
                            {--pretend : List what would be queued and queue nothing}';

    /**
     * @var string
     */
    protected $description = 'Queue a BrickLink price refresh for every owned set';

    public function handle(): int
    {
        // One lookup per distinct owned set; owning multiple copies of a set is
        // still a single price refresh.
        //
        // Deliberately not filtered on bricklink_id. That column is an
        // override, empty on anything never priced and null once an override is
        // cleared, and bricklink_lookup_number falls back to the Brickset
        // number — so the old whereNotNull() guard excluded nothing today and
        // would have silently dropped a set from the monthly refresh the moment
        // someone cleared its override.
        $items = CatalogItem::query()
            ->whereHas('sets')
            ->when($this->option('id'), fn ($query, $ids) => $query->whereIn('id', $ids))
            ->orderBy('set_number')
            ->get();

        if ($items->isEmpty()) {
            $this->info('No owned sets to refresh.');

            return self::SUCCESS;
        }

        if ($this->option('pretend')) {
            $this->table(
                ['id', 'set', 'name'],
                $items->map(fn (CatalogItem $item) => [$item->id, $item->bricklink_lookup_number, $item->name])->all()
            );
            $this->info("Would queue {$items->count()} price refresh(es).");

            return self::SUCCESS;
        }

        if ($this->hasNoQueueWorker()) {
            return $this->workThroughHere(
                items: $items,
                pauseSeconds: self::PAUSE_SECONDS,
                doneVerb: 'Repriced',
                failures: [
                    BricklinkPriceException::class => 'BrickLink returned no price',
                ],
                work: fn (CatalogItem $item) => (new UpdateCatalogItem($item))->updateBricklink(),
            );
        }

        $batch = Bus::batch(
            $items->map(fn (CatalogItem $item) => new RefreshCatalogItemPrice($item))
        )
            ->name('Collection price refresh')
            ->allowFailures()
            ->dispatch();

        // Deliberately no snapshot on completion: the collection log holds one
        // entry per month, written by the scheduled collection:snapshot on the
        // last day. A batch-completion snapshot would add a second, extra entry
        // dated whenever the batch happened to finish.
        $this->info("Queued a BrickLink price refresh for {$items->count()} sets (batch {$batch->id}).");

        return self::SUCCESS;
    }
}
