<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The BrickLink values are a snapshot, refreshed by the monthly
 * collection:refresh-prices batch, but nothing recorded when a given set was
 * last looked up — updated_at moves for any edit at all, so it cannot answer
 * it. Rows priced before this migration stay null and read as unknown rather
 * than being backfilled from a timestamp that does not mean this.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('catalog_items', 'prices_updated_at')) {
            return;
        }

        Schema::table('catalog_items', function (Blueprint $table) {
            $table->timestamp('prices_updated_at')->nullable()->after('current_value_new');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('catalog_items', 'prices_updated_at')) {
            return;
        }

        Schema::table('catalog_items', function (Blueprint $table) {
            $table->dropColumn('prices_updated_at');
        });
    }
};
