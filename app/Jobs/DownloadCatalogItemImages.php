<?php

namespace App\Jobs;

use App\Models\CatalogItem;
use App\UpdateCatalogItem;
use DateTimeInterface;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class DownloadCatalogItemImages implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Unlimited attempts, bounded instead by retryUntil() (for rate-limit
     * releases) and maxExceptions (for real failures) — the same shape as
     * RefreshCatalogItemPrice, for the same reason.
     */
    public int $tries = 0;

    public int $maxExceptions = 3;

    public int $backoff = 60;

    public function __construct(public CatalogItem $catalogItem, public bool $force = false)
    {
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        (new UpdateCatalogItem($this->catalogItem))->updateImages($this->force);
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new RateLimited('brickset')];
    }

    /**
     * Keep retrying through rate-limit releases for a few days, so a backfill
     * of the whole catalogue can spread itself out instead of failing.
     */
    public function retryUntil(): DateTimeInterface
    {
        return now()->addDays(3);
    }

    /**
     * A set with no picture on Brickset will fail here every time, so log it
     * rather than leaving the gap silent.
     */
    public function failed(Throwable $e): void
    {
        Log::error("Brickset image download gave up for {$this->catalogItem->item_number}: ".$e->getMessage());
    }
}
