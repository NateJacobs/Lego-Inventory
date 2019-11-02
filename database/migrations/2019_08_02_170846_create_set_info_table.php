<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSetInfoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('catalog_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('set_number', 25);
            $table->integer('set_number_variant');
            $table->string('bricklink_id', 100)->nullable();
            $table->integer('brickset_id');
            $table->string('name', 255);
            $table->integer('piece_count');
            $table->integer('minifig_count');
            $table->float('retail_price', 8, 2);
            $table->float('current_value_used', 8, 2)->nullable();
            $table->float('current_value_new', 8, 2)->nullable();
            $table->year('year');
            $table->string('theme', 100);
            $table->string('sub_theme', 100);
            $table->string('theme_group', 100);
            $table->string('type', 50);
            $table->string('image_path', 255)->nullable();
            $table->string('thumbnail_path', 255)->nullable();
            $table->string('brickset_url', 255);
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
        Schema::dropIfExists('catalog_items');
    }
}
