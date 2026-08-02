<?php

namespace App\Console\Commands;

use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Regenerates the files ReferenceDataSeeder loads.
 *
 * Only the shared reference tables are exported. Personal collection data is
 * never written here — this repository is public, and the seed files are
 * committed.
 */
class ExportSeedData extends Command
{
    /**
     * @var string
     */
    protected $signature = 'collection:export-seed-data';

    /**
     * @var string
     */
    protected $description = 'Export the shared reference tables to the JSON used by ReferenceDataSeeder';

    public function handle(): int
    {
        $directory = database_path('seeders/data');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $this->info('Exporting reference data from ['.DB::getDatabaseName().']:');

        foreach (ReferenceDataSeeder::TABLES as $table) {
            $rows = DB::table($table)->orderBy('id')->get()
                ->map(fn ($row): array => (array) $row)
                ->all();

            $path = ReferenceDataSeeder::dataPath($table);

            // Pretty printed and uncommitted-to-gzip so the data stays readable
            // and reviewable in diffs.
            file_put_contents(
                $path,
                json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n",
            );

            $this->line(sprintf(
                '  %s %s rows  (%s KB)',
                str_pad($table, 12),
                str_pad(number_format(count($rows)), 7, ' ', STR_PAD_LEFT),
                number_format(filesize($path) / 1024, 1),
            ));
        }

        return self::SUCCESS;
    }
}
