<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * collection:snapshot already treats the date as the key — it does an
 * updateOrCreate on it, so a second run on the same day refreshes the row
 * rather than adding one. Nothing enforced that, so two runs racing, or a
 * hand-written insert, could still have produced two rows for one day and
 * quietly doubled a point on every chart drawn from this table.
 *
 * Deliberately on the date and not on the year and month. The log holds one
 * entry a month by habit, not by rule: the first fifteen years of this table
 * are dated wherever the reading happened to fall, and December 2018 holds two
 * genuine readings nineteen days apart. A month-level constraint would call
 * that history invalid.
 */
return new class extends Migration
{
    protected const INDEX = 'collection_logs_date_unique';

    public function up(): void
    {
        if (Schema::hasIndex('collection_logs', self::INDEX)) {
            return;
        }

        // Fail with something readable rather than a bare 1062 from the index
        // build, naming the days that have to be settled first.
        $duplicates = DB::table('collection_logs')
            ->select('date')
            ->groupBy('date')
            ->havingRaw('count(*) > 1')
            ->pluck('date');

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                'collection_logs holds more than one row for '.$duplicates->implode(', ')
                .'. Decide which to keep before this index can be added.'
            );
        }

        Schema::table('collection_logs', function (Blueprint $table) {
            $table->unique('date');
        });
    }

    public function down(): void
    {
        if (! Schema::hasIndex('collection_logs', self::INDEX)) {
            return;
        }

        Schema::table('collection_logs', function (Blueprint $table) {
            $table->dropUnique(self::INDEX);
        });
    }
};
