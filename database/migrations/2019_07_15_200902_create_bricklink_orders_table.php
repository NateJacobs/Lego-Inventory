<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBricklinkOrdersTable extends Migration
{
    /**
     * Parts orders placed through BrickLink. order_cost and shipping_cost are
     * optional because older orders were only ever recorded as a single total;
     * total_cost is what the collection valuation actually reads.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bricklink_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('purchase_date');
            $table->string('seller_name', 255);
            $table->string('store_name', 255)->nullable()->default('');
            // BrickLink order numbers are wider than a signed int.
            $table->bigInteger('order_number');
            $table->integer('pieces');
            $table->decimal('order_cost', 10, 2)->nullable();
            $table->decimal('shipping_cost', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2);
            $table->json('details')->nullable();
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
        Schema::dropIfExists('bricklink_orders');
    }
}
