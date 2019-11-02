<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCollectionLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('collection_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('date');
            $table->float('used_value', 8, 2);
            $table->float('new_value', 8, 2);
            $table->float('retail_value', 8, 2);
            $table->integer('total_sets');
            $table->integer('piece_count');
            $table->text('notes');
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
