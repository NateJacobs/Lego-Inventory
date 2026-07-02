<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The application's models and Nova resources drifted ahead of the
     * committed migrations: they reference columns that were only ever added to
     * the production database by hand, and they leave some NOT NULL columns
     * unset, so inserts are rejected under strict SQL mode. This migration
     * brings a freshly-migrated schema in line with what the code expects.
     *
     * Every column add is guarded with hasColumn so it is a no-op on a database
     * that already has the column (i.e. production). Relaxing a column to
     * nullable is likewise safe to run against an already-nullable column.
     */
    public function up(): void
    {
        // Sets resource shows/searches a Notes field.
        Schema::table('sets', function (Blueprint $table) {
            if (! Schema::hasColumn('sets', 'notes')) {
                $table->text('notes')->nullable();
            }
        });

        // BulkBrick model/resource use cost, acquired_date, notes and an
        // acquired_location relation; type/brick_price are never entered.
        Schema::table('bulk_bricks', function (Blueprint $table) {
            if (! Schema::hasColumn('bulk_bricks', 'cost')) {
                $table->decimal('cost', 10, 2)->nullable()->after('value');
            }
            if (! Schema::hasColumn('bulk_bricks', 'acquired_date')) {
                $table->date('acquired_date')->nullable();
            }
            if (! Schema::hasColumn('bulk_bricks', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (! Schema::hasColumn('bulk_bricks', 'acquired_location_id')) {
                $table->unsignedBigInteger('acquired_location_id')->nullable();
            }

            $table->string('type', 25)->nullable()->change();
            $table->float('brick_price')->nullable()->change();
        });

        // BricklinkOrder resource shows a Notes field; Details is disabled.
        Schema::table('bricklink_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('bricklink_orders', 'notes')) {
                $table->text('notes')->nullable();
            }

            $table->longText('details')->nullable()->change();
        });

        // StorageLocation resource only captures the name.
        Schema::table('storage_locations', function (Blueprint $table) {
            $table->string('city', 100)->nullable()->change();
            $table->string('state', 15)->nullable()->change();
            $table->integer('zip_code')->nullable()->change();
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
            foreach (['cost', 'acquired_date', 'notes', 'acquired_location_id'] as $col) {
                if (Schema::hasColumn('bulk_bricks', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('bricklink_orders', function (Blueprint $table) {
            if (Schema::hasColumn('bricklink_orders', 'notes')) {
                $table->dropColumn('notes');
            }
        });

        // Nullability relaxations are left in place on rollback.
    }
};
