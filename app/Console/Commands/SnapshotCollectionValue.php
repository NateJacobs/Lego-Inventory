<?php

namespace App\Console\Commands;

use App\Models\CollectionLog;
use App\Services\CollectionValuation;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SnapshotCollectionValue extends Command
{
    /**
     * @var string
     */
    protected $signature = 'collection:snapshot
                            {--date= : Date to record the snapshot for (defaults to today)}';

    /**
     * @var string
     */
    protected $description = 'Record a dated snapshot of the collection\'s total value';

    public function handle(CollectionValuation $valuation): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->toDateString()
            : today()->toDateString();

        // Keyed on date so re-running on the same day refreshes rather than duplicates.
        $log = CollectionLog::updateOrCreate(
            ['date' => $date],
            array_merge($valuation->toArray(), ['notes' => 'Automated snapshot']),
        );

        $this->info(sprintf(
            'Collection snapshot for %s — new $%s / used $%s / retail $%s across %s sets (%s pieces).',
            $date,
            number_format($log->new_value, 2),
            number_format($log->used_value, 2),
            number_format($log->retail_value, 2),
            number_format($log->total_sets),
            number_format($log->piece_count),
        ));

        return self::SUCCESS;
    }
}
