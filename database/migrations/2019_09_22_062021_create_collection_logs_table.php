<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectionLogsTable extends Migration
{
    /**
     * A dated snapshot of what the whole collection is worth, written monthly
     * by collection:snapshot. These are sums across every set, bulk lot and
     * order, so they get more headroom than the per-item money columns.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('collection_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('date')->nullable();
            $table->decimal('retail_value', 12, 2)->nullable();
            $table->decimal('used_value', 12, 2);
            $table->decimal('new_value', 12, 2);
            $table->integer('total_sets');
            $table->integer('piece_count');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('collection_logs');
    }
}
