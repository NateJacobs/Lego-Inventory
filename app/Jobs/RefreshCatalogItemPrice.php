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

class RefreshCatalogItemPrice implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Unlimited attempts, bounded instead by retryUntil() (for rate-limit
     * releases) and maxExceptions (for real failures).
     */
    public int $tries = 0;

    /**
     * Give up on a job that keeps throwing, but note that rate-limit releases
     * are not exceptions and so do not count against this.
     */
    public int $maxExceptions = 3;

    /**
     * Seconds to wait before retrying after an exception.
     */
    public int $backoff = 60;

    public function __construct(public CatalogItem $catalogItem)
    {
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        (new UpdateCatalogItem($this->catalogItem))->updateBricklink();
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new RateLimited('bricklink')];
    }

    /**
     * Keep retrying through rate-limit releases for a few days so a large
     * collection can spread its refresh across BrickLink's daily request cap.
     */
    public function retryUntil(): DateTimeInterface
    {
        return now()->addDays(3);
    }

    /**
     * Called once the job has exhausted its retries. Log it so a set that
     * keeps failing to price is visible rather than silently skipped.
     */
    public function failed(Throwable $e): void
    {
        Log::error("BrickLink price refresh gave up for {$this->catalogItem->bricklink_id}: ".$e->getMessage());
    }
}
