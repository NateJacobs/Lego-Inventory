<?php

namespace App\Console\Commands;

use App\Exceptions\BricksetImageException;
use App\Exceptions\BricksetLookupException;
use App\Jobs\DownloadCatalogItemImages;
use App\Models\CatalogItem;
use App\Services\BricksetClient;
use App\UpdateCatalogItem;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;

class DownloadCollectionImages extends Command
{
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

        // Without a worker the rate limiter cannot do its job: a sync job it
        // holds back is released, and nothing ever picks a released sync job
        // up again, so it is dropped in silence. Do the work here instead.
        if (config('queue.default') === 'sync') {
            return $this->downloadHere($items, $force);
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

    /**
     * Seconds between lookups when running without a worker, matching the
     * 'brickset' limiter the queued path would have applied.
     */
    protected const PAUSE_SECONDS = 1;

    /**
     * @param  Collection<int, CatalogItem>  $items
     */
    protected function downloadHere(Collection $items, bool $force): int
    {
        $minutes = (int) ceil($items->count() * self::PAUSE_SECONDS / 60);

        $this->warn('QUEUE_CONNECTION is sync, so there is no worker to hand these to.');
        $this->line("Downloading them here instead, about {$minutes} minute(s). Stopping and re-running picks up where it left off.");
        $this->newLine();

        $bar = $this->output->createProgressBar($items->count());
        $bar->start();

        $downloaded = 0;
        $unknown = [];
        $noPicture = [];

        foreach ($items as $item) {
            try {
                (new UpdateCatalogItem($item))->updateImages($force);
                $downloaded++;
            } catch (BricksetLookupException $e) {
                // Brickset does not have this set number at all, which the
                // rows imported from the old spreadsheet are prone to.
                $unknown[] = $item->item_number;
            } catch (BricksetImageException $e) {
                $noPicture[] = $item->item_number;
            }

            $bar->advance();
            sleep(self::PAUSE_SECONDS);
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Downloaded images for {$downloaded} of {$items->count()} set(s).");
        $this->report('Brickset has no such set', $unknown);
        $this->report('Found on Brickset but no picture to download', $noPicture);

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $itemNumbers
     */
    protected function report(string $heading, array $itemNumbers): void
    {
        if (empty($itemNumbers)) {
            return;
        }

        $this->newLine();
        $this->warn($heading.' ('.count($itemNumbers).'):');
        $this->line('  '.implode(', ', array_slice($itemNumbers, 0, 25))
            .(count($itemNumbers) > 25 ? ', …' : ''));
    }
}
