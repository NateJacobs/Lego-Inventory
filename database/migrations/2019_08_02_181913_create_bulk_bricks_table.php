<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBulkBricksTable extends Migration
{
    /**
     * Loose bricks bought by weight or by the lot, rather than as a set. Every
     * lot is entered by hand, so all of it is required.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bulk_bricks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('acquired_location_id');
            $table->integer('piece_count');
            $table->decimal('cost', 10, 2);
            $table->decimal('value', 10, 2);
            $table->date('acquired_date');
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
        Schema::dropIfExists('bulk_bricks');
    }
}
