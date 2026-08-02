<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the columns that were once only ever created by hand on the live
     * database, for any environment still missing them.
     *
     * The create_* migrations now describe the real schema directly, so on a
     * freshly migrated database every check below is a no-op. What this
     * migration must not do is alter types or nullability: that is what pulled
     * a fresh schema away from the real one in the first place, and it is now
     * handled once, in 2026_08_02_000001_align_schema_with_database.
     */
    public function up(): void
    {
        Schema::table('sets', function (Blueprint $table) {
            if (! Schema::hasColumn('sets', 'notes')) {
                $table->text('notes')->nullable();
            }
        });

        Schema::table('bulk_bricks', function (Blueprint $table) {
            if (! Schema::hasColumn('bulk_bricks', 'cost')) {
                $table->decimal('cost', 10, 2)->nullable();
            }
            if (! Schema::hasColumn('bulk_bricks', 'acquired_date')) {
                $table->date('acquired_date')->nullable();
            }
            if (! Schema::hasColumn('bulk_bricks', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (! Schema::hasColumn('bulk_bricks', 'acquired_location_id')) {
                $table->bigInteger('acquired_location_id')->nullable();
            }
        });

        Schema::table('bricklink_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('bricklink_orders', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (! Schema::hasColumn('bricklink_orders', 'total_cost')) {
                $table->decimal('total_cost', 10, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sets', function (Blueprint $table) {
            if (Schema::hasColumn('sets', 'notes')) {
                $table->dropColumn('notes');
            }
        });

        Schema::table('bulk_bricks', function (Blueprint $table) {
            foreach (['cost', 'acquired_date', 'notes', 'acquired_location_id'] as $column) {
                if (Schema::hasColumn('bulk_bricks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('bricklink_orders', function (Blueprint $table) {
            foreach (['notes', 'total_cost'] as $column) {
                if (Schema::hasColumn('bricklink_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
