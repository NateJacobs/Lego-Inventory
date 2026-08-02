<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSetInfoTable extends Migration
{
    /**
     * The catalogue of LEGO sets. Almost every column is filled in by
     * CatalogItemObserver from Brickset and BrickLink rather than typed in, so
     * they are nullable: a lookup that comes back without a piece count or a
     * price still has to produce a usable row.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('catalog_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('brickset_id')->nullable();
            $table->string('set_number', 25)->default('');
            $table->integer('set_number_variant');
            $table->string('bricklink_id', 100)->nullable()->default('');
            $table->string('name', 255)->nullable()->default('');
            $table->integer('piece_count')->nullable();
            $table->integer('minifig_count')->nullable();
            $table->decimal('retail_price', 10, 2)->nullable();
            $table->decimal('current_value_used', 10, 2)->nullable();
            $table->decimal('current_value_new', 10, 2)->nullable();
            $table->year('year');
            // Related to themes, but intentionally not a foreign key:
            // CatalogItemObserver stores 0 in subtheme_id to mean "no subtheme",
            // which a constraint would reject.
            $table->integer('theme_id')->nullable();
            $table->integer('subtheme_id')->nullable();
            $table->string('theme_group', 100);
            $table->string('image_path', 255)->nullable()->default('');
            $table->string('thumbnail_path', 255)->nullable();
            $table->string('brickset_url', 255);
            $table->string('type', 50)->nullable();
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
