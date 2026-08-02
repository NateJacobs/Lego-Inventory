<?php

namespace App\Console\Commands;

use App\Jobs\RefreshCatalogItemPrice;
use App\Models\CatalogItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class RefreshCollectionPrices extends Command
{
    /**
     * @var string
     */
    protected $signature = 'collection:refresh-prices';

    /**
     * @var string
     */
    protected $description = 'Queue a BrickLink price refresh for every owned set';

    public function handle(): int
    {
        // One lookup per distinct owned set that has a BrickLink id; owning
        // multiple copies of a set is still a single price refresh.
        $items = CatalogItem::whereHas('sets')->whereNotNull('bricklink_id')->get();

        if ($items->isEmpty()) {
            $this->info('No owned sets with a BrickLink id to refresh.');

            return self::SUCCESS;
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
