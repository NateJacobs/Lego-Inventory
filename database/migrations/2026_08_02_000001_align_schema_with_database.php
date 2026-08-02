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
     *
     * Every step checks the column exists first. Environments reached this
     * point by different routes — some columns were only ever added by hand —
     * and a migration that assumes one of them is present fails halfway,
     * leaving the schema in a state no later migration expects.
     */
    public function up(): void
    {
        // Per-item money: prices, costs and values of a single thing.
        $this->change('catalog_items', [
            'retail_price' => fn (Blueprint $table) => $table->decimal('retail_price', 10, 2)->nullable()->change(),
            'current_value_used' => fn (Blueprint $table) => $table->decimal('current_value_used', 10, 2)->nullable()->change(),
            'current_value_new' => fn (Blueprint $table) => $table->decimal('current_value_new', 10, 2)->nullable()->change(),
        ]);

        $this->change('sets', [
            'purchase_price' => fn (Blueprint $table) => $table->decimal('purchase_price', 10, 2)->nullable()->default(0)->change(),
        ]);

        $this->backfill('bulk_bricks', ['cost' => 0, 'value' => 0, 'piece_count' => 0]);

        $this->change('bulk_bricks', [
            'cost' => fn (Blueprint $table) => $table->decimal('cost', 10, 2)->change(),
            'value' => fn (Blueprint $table) => $table->decimal('value', 10, 2)->change(),
        ]);

        // total_cost only ever reached some databases by hand. Create it where
        // it is missing rather than assuming an earlier migration did, and seed
        // it from the breakdown so the column can be made NOT NULL below.
        if (! Schema::hasColumn('bricklink_orders', 'total_cost')) {
            Schema::table('bricklink_orders', function (Blueprint $table) {
                $table->decimal('total_cost', 10, 2)->nullable()->after('shipping_cost');
            });

            DB::table('bricklink_orders')->update([
                'total_cost' => DB::raw('COALESCE(order_cost, 0) + COALESCE(shipping_cost, 0)'),
            ]);
        }

        $this->backfill('bricklink_orders', ['total_cost' => 0]);

        $this->change('bricklink_orders', [
            'order_cost' => fn (Blueprint $table) => $table->decimal('order_cost', 10, 2)->nullable()->change(),
            'shipping_cost' => fn (Blueprint $table) => $table->decimal('shipping_cost', 10, 2)->nullable()->change(),
            'total_cost' => fn (Blueprint $table) => $table->decimal('total_cost', 10, 2)->change(),
            // Orders bought direct from a seller have no store name.
            'store_name' => fn (Blueprint $table) => $table->string('store_name', 255)->nullable()->default('')->change(),
        ]);

        // Collection totals: sums across everything, so wider than the above.
        $this->backfill('collection_logs', ['used_value' => 0, 'new_value' => 0]);

        $this->change('collection_logs', [
            'retail_value' => fn (Blueprint $table) => $table->decimal('retail_value', 12, 2)->nullable()->change(),
            'used_value' => fn (Blueprint $table) => $table->decimal('used_value', 12, 2)->change(),
            'new_value' => fn (Blueprint $table) => $table->decimal('new_value', 12, 2)->change(),
        ]);

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
     * Apply each definition, skipping any column this database does not have.
     *
     * @param  array<string, callable(Blueprint): mixed>  $columns
     */
    protected function change(string $table, array $columns): void
    {
        $present = array_filter(
            $columns,
            fn (string $column): bool => Schema::hasColumn($table, $column),
            ARRAY_FILTER_USE_KEY,
        );

        if ($present === []) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($present): void {
            foreach ($present as $definition) {
                $definition($blueprint);
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
