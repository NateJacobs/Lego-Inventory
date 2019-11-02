<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBricklinkOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bricklink_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('purchase_date');
            $table->string('seller_name', 255);
            $table->string('store_name', 255);
            $table->integer('order_number');
            $table->integer('pieces');
            $table->float('order_cost', 8, 2);
            $table->float('shipping_cost', 8, 2);
            $table->json('details');
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
        Schema::dropIfExists('bricklink_orders');
    }
}
