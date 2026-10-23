<?php

namespace App\Console\Commands\Concerns;

use App\Models\CatalogItem;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Lets a command that would hand its work to the queue do that work itself
 * when the queue connection is sync.
 *
 * This is not a convenience, and it is not about whether a worker exists: on
 * the sync connection a dispatched job runs inline in the dispatching process
 * and a worker never sees it, however many are running. A sync job the
 * RateLimited middleware holds back is released, and nothing ever picks a
 * released sync job up again, so it goes with no error, no log line and no
 * retry — a batch of rate-limited jobs quietly loses almost all of itself,
 * which is far worse than taking longer.
 */
trait WorksWithoutAQueue
{
    /**
     * Whether a dispatched job would run inline rather than reach a worker.
     */
    protected function queueRunsInline(): bool
    {
        return config('queue.default') === 'sync';
    }

    /**
     * Work through the items here, pacing them to match the rate limiter the
     * queued path would have applied.
     *
     * Note that only the per-minute pace carries over; there is no daily cap
     * here, so a collection far larger than this one wants a real worker.
     *
     * @param  Collection<int, CatalogItem>  $items
     * @param  int  $pauseSeconds  Delay between items, matching the limiter.
     * @param  string  $doneVerb  Leads the closing count, e.g. "Repriced".
     * @param  array<class-string<Throwable>, string>  $failures  Exceptions to expect, and what to call them.
     * @param  callable(CatalogItem): void  $work  Throws to report a failure.
     */
    protected function workThroughHere(
        Collection $items,
        int $pauseSeconds,
        string $doneVerb,
        array $failures,
        callable $work,
    ): int {
        $minutes = (int) ceil($items->count() * $pauseSeconds / 60);

        $this->warn('QUEUE_CONNECTION is sync, so a dispatched job would run inline here anyway '
            .'and never reach a worker.');
        $this->line("Working through them here instead, about {$minutes} minute(s). "
            .'Stopping and re-running picks up where it left off.');
        $this->newLine();

        $bar = $this->output->createProgressBar($items->count());
        $bar->start();

        $done = 0;
        $caught = [];

        foreach ($items as $item) {
            try {
                $work($item);
                $done++;
            } catch (Throwable $e) {
                $heading = $this->headingFor($e, $failures);

                // Only the failures this command said to expect. Anything else
                // is a bug, and swallowing it behind a progress bar would hide
                // it for the length of the run.
                if ($heading === null) {
                    $bar->finish();
                    $this->newLine(2);

                    throw $e;
                }

                $caught[$heading][] = $item->item_number;
            }

            $bar->advance();
            sleep($pauseSeconds);
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("{$doneVerb} {$done} of {$items->count()} set(s).");

        foreach ($caught as $heading => $itemNumbers) {
            $this->report($heading, $itemNumbers);
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<class-string<Throwable>, string>  $failures
     */
    protected function headingFor(Throwable $e, array $failures): ?string
    {
        foreach ($failures as $class => $heading) {
            if ($e instanceof $class) {
                return $heading;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $itemNumbers
     */
    protected function report(string $heading, array $itemNumbers): void
    {
        $this->newLine();
        $this->warn($heading.' ('.count($itemNumbers).'):');
        $this->line('  '.implode(', ', array_slice($itemNumbers, 0, 25))
            .(count($itemNumbers) > 25 ? ', …' : ''));
    }
}
