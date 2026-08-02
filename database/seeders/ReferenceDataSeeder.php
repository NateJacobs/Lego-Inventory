<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use JsonException;

/**
 * Seeds the LEGO reference data a fresh install needs before it can catalogue
 * anything — currently the theme taxonomy that CatalogItemObserver resolves
 * Brickset's theme names against.
 *
 * Only data that is the same for every install belongs here. The collection
 * itself — owned sets, purchases, storage, bulk lots, order history and the
 * value log — is personal and deliberately stays out of the repository; move
 * that between machines with a database dump instead.
 *
 * Regenerate with `php artisan collection:export-seed-data`.
 */
class ReferenceDataSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    public const TABLES = [
        'themes',
    ];

    /**
     * Inserted in batches to stay well under max_allowed_packet.
     */
    protected const CHUNK = 250;

    public function run(): void
    {
        foreach (self::TABLES as $table) {
            $this->seedTable($table);
        }
    }

    protected function seedTable(string $table): void
    {
        $path = self::dataPath($table);

        if (! is_file($path)) {
            $this->command?->warn("  {$table}: no export found, skipping.");

            return;
        }

        // Never overwrite what is already there — seeding is for a fresh
        // database. Use `migrate:fresh --seed` to rebuild from scratch.
        if (DB::table($table)->exists()) {
            $this->command?->warn("  {$table}: already has rows, left untouched.");

            return;
        }

        $rows = $this->read($path);

        foreach (array_chunk($rows, self::CHUNK) as $chunk) {
            DB::table($table)->insert($chunk);
        }

        $this->command?->info('  '.str_pad($table, 12).number_format(count($rows)).' rows');
    }

    /**
     * @return list<array<string, mixed>>
     *
     * @throws JsonException
     */
    protected function read(string $path): array
    {
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    public static function dataPath(string $table): string
    {
        return database_path("seeders/data/{$table}.json");
    }
}
