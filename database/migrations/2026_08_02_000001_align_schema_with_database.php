<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The committed migrations and the live database had drifted apart over
     * several years of hand-edits, to the point that a fresh `migrate` produced
     * a schema the real data would not load into. The create_* migrations now
     * describe the live schema; this migration moves an existing database the
     * remaining distance so both ends agree.
     *
     * Money moves from double to decimal. A double stores money approximately —
     * 0.1 + 0.2 is famously not 0.3 — and Laravel's schema builder can no longer
     * express the double(8,2) the database happened to be using, so the two
     * could never have been described by the same migration. decimal is exact
     * and covers the same range.
     */
    public function up(): void
    {
        // Per-item money: prices, costs and values of a single thing.
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->decimal('retail_price', 10, 2)->nullable()->change();
            $table->decimal('current_value_used', 10, 2)->nullable()->change();
            $table->decimal('current_value_new', 10, 2)->nullable()->change();
        });

        Schema::table('sets', function (Blueprint $table) {
            $table->decimal('purchase_price', 10, 2)->nullable()->default(0)->change();
        });

        $this->backfill('bulk_bricks', ['cost' => 0, 'value' => 0, 'piece_count' => 0]);

        Schema::table('bulk_bricks', function (Blueprint $table) {
            $table->decimal('cost', 10, 2)->change();
            $table->decimal('value', 10, 2)->change();
        });

        $this->backfill('bricklink_orders', ['total_cost' => 0]);

        Schema::table('bricklink_orders', function (Blueprint $table) {
            $table->decimal('order_cost', 10, 2)->nullable()->change();
            $table->decimal('shipping_cost', 10, 2)->nullable()->change();
            $table->decimal('total_cost', 10, 2)->change();
            // Orders bought direct from a seller have no store name.
            $table->string('store_name', 255)->nullable()->default('')->change();
        });

        // Collection totals: sums across everything, so wider than the above.
        $this->backfill('collection_logs', ['used_value' => 0, 'new_value' => 0]);

        Schema::table('collection_logs', function (Blueprint $table) {
            $table->decimal('retail_value', 12, 2)->nullable()->change();
            $table->decimal('used_value', 12, 2)->change();
            $table->decimal('new_value', 12, 2)->change();
        });

        // Created by the original migration but never used by the application,
        // and long since gone from the live database.
        Schema::table('bulk_bricks', function (Blueprint $table) {
            foreach (['type', 'brick_price'] as $column) {
                if (Schema::hasColumn('bulk_bricks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Columns that are about to become NOT NULL have to hold a value first.
     *
     * @param  array<string, mixed>  $defaults
     */
    protected function backfill(string $table, array $defaults): void
    {
        foreach ($defaults as $column => $value) {
            if (Schema::hasColumn($table, $column)) {
                DB::table($table)->whereNull($column)->update([$column => $value]);
            }
        }
    }

    public function down(): void
    {
        // The double(8,2) these columns used to be cannot be expressed through
        // the schema builder, so rolling back lands on the framework's own
        // float. No data is lost: the values fit either way.
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->float('retail_price')->nullable()->change();
            $table->float('current_value_used')->nullable()->change();
            $table->float('current_value_new')->nullable()->change();
        });

        Schema::table('sets', function (Blueprint $table) {
            $table->float('purchase_price')->nullable()->default(0)->change();
        });

        Schema::table('bulk_bricks', function (Blueprint $table) {
            $table->float('cost')->change();
            $table->float('value')->change();
            $table->string('type', 25)->nullable();
            $table->float('brick_price')->nullable();
        });

        Schema::table('bricklink_orders', function (Blueprint $table) {
            $table->float('order_cost')->nullable()->change();
            $table->float('shipping_cost')->nullable()->change();
            $table->float('total_cost')->change();
        });

        Schema::table('collection_logs', function (Blueprint $table) {
            $table->float('retail_value')->nullable()->change();
            $table->float('used_value')->change();
            $table->float('new_value')->change();
        });
    }
};
