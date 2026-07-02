<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * collection_logs stores the summed value of the whole collection. The
     * value columns were float(8,2), capping totals at $999,999.99 and using
     * approximate floating-point storage for money. Widen them to a precise
     * decimal(12,2). The notes column was also NOT NULL with no default, which
     * would reject any snapshot written without a note under strict SQL mode.
     */
    public function up(): void
    {
        Schema::table('collection_logs', function (Blueprint $table) {
            $table->decimal('new_value', 12, 2)->change();
            $table->decimal('used_value', 12, 2)->change();
            $table->decimal('retail_value', 12, 2)->change();
            $table->text('notes')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('collection_logs', function (Blueprint $table) {
            $table->float('new_value', 8, 2)->change();
            $table->float('used_value', 8, 2)->change();
            $table->float('retail_value', 8, 2)->change();
            $table->text('notes')->nullable(false)->change();
        });
    }
};
