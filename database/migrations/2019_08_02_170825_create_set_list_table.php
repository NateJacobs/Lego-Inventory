<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSetListTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('catalog_item_id');
            $table->bigInteger('storage_location_id');
            $table->bigInteger('acquired_location_id');
            $table->float('purchase_price', 8, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->string('current_condition', 15);
            $table->softDeletes();
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
        Schema::dropIfExists('sets');
    }
}
