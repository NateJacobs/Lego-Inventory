<?php

namespace App\Console\Commands;

use App\Jobs\RefreshCatalogItemPrice;
use App\Models\CatalogItem;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
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
    protected $description = 'Queue a BrickLink price refresh for every owned set, then snapshot the collection value';

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
            ->finally(function (Batch $batch) {
                // Record the collection's value once every price has been
                // attempted, regardless of individual failures.
                Artisan::call('collection:snapshot');
            })
            ->dispatch();

        $this->info("Queued a BrickLink price refresh for {$items->count()} sets (batch {$batch->id}).");
        $this->line('A collection value snapshot will be recorded when the batch finishes.');

        return self::SUCCESS;
    }
}
