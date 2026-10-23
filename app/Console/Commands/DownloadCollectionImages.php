<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\WorksWithoutAQueue;
use App\Exceptions\BricksetImageException;
use App\Exceptions\BricksetLookupException;
use App\Jobs\DownloadCatalogItemImages;
use App\Models\CatalogItem;
use App\Services\BricksetClient;
use App\UpdateCatalogItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class DownloadCollectionImages extends Command
{
    use WorksWithoutAQueue;

    /**
     * Seconds between lookups when running without a worker, matching the
     * 'brickset' limiter the queued path would have applied.
     */
    protected const PAUSE_SECONDS = 1;

    /**
     * @var string
     */
    protected $signature = 'collection:download-images
                            {--force : Fetch again even where both files are already stored}
                            {--id=* : Limit to these catalog item ids}
                            {--pretend : List what would be queued and queue nothing}';

    /**
     * @var string
     */
    protected $description = 'Queue a Brickset image download for every catalog item whose pictures are missing';

    public function handle(): int
    {
        if (! BricksetClient::isConfigured()) {
            $this->error('MURSTEN_TRACK_KEY is not set, so Brickset cannot be asked for anything.');

            return self::FAILURE;
        }

        $force = (bool) $this->option('force');

        // The disk check is a stat() per item, so do it here once rather than
        // queueing 2,000 jobs that mostly decide they had nothing to do.
        $items = CatalogItem::query()
            ->when($this->option('id'), fn ($query, $ids) => $query->whereIn('id', $ids))
            ->orderBy('set_number')
            ->get()
            ->reject(fn (CatalogItem $item) => ! $force && $item->hasStoredImages());

        if ($items->isEmpty()) {
            $this->info('Every catalog item already has its pictures.');

            return self::SUCCESS;
        }

        if ($this->option('pretend')) {
            $this->table(
                ['id', 'set', 'name'],
                $items->map(fn (CatalogItem $item) => [$item->id, $item->item_number, $item->name])->all()
            );
            $this->info("Would queue {$items->count()} image download(s).");

            return self::SUCCESS;
        }

        if ($this->hasNoQueueWorker()) {
            return $this->workThroughHere(
                items: $items,
                pauseSeconds: self::PAUSE_SECONDS,
                doneVerb: 'Downloaded images for',
                failures: [
                    BricksetLookupException::class => 'Brickset has no such set',
                    BricksetImageException::class => 'Found on Brickset but no picture to download',
                ],
                work: fn (CatalogItem $item) => (new UpdateCatalogItem($item))->updateImages($force),
            );
        }

        $batch = Bus::batch(
            $items->map(fn (CatalogItem $item) => new DownloadCatalogItemImages($item, $force))
        )
            ->name('Collection image download')
            ->allowFailures()
            ->dispatch();

        $this->info("Queued an image download for {$items->count()} set(s) (batch {$batch->id}).");
        $this->line('A set Brickset has no picture for will fail; those are logged, not retried forever.');

        return self::SUCCESS;
    }
}
