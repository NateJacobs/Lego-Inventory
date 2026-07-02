<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The BulkBrick model, its Nova resource, and the piece-count metrics all
     * reference a piece_count column, but the original create_bulk_bricks
     * migration never defined one, so bulk lots contributed 0 pieces to the
     * collection's piece count. Add it (guarded, since production may already
     * have the column).
     */
    public function up(): void
    {
        Schema::table('bulk_bricks', function (Blueprint $table) {
            if (! Schema::hasColumn('bulk_bricks', 'piece_count')) {
                $table->integer('piece_count')->nullable()->after('value');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bulk_bricks', function (Blueprint $table) {
            if (Schema::hasColumn('bulk_bricks', 'piece_count')) {
                $table->dropColumn('piece_count');
            }
        });
    }
};
