<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSetListTable extends Migration
{
    /**
     * The individual owned copies of a catalog item. A copy always belongs to a
     * catalog item and has a condition; where it is stored and where it came
     * from are optional, since older records predate both.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('catalog_item_id');
            $table->bigInteger('storage_location_id')->nullable();
            $table->bigInteger('acquired_location_id')->nullable();
            $table->decimal('purchase_price', 10, 2)->nullable()->default(0);
            $table->date('purchase_date')->nullable();
            $table->string('current_condition', 15);
            $table->text('notes')->nullable();
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
