<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCodingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('codings', function (Blueprint $table) {
            $table->id();
            $table->integer('inventory_coding');
            $table->integer('receipt_coding');
            $table->integer('customer_order_coding');
            $table->integer('input_coding');
            $table->integer('output_coding');
            $table->integer('shipping_coding');
            $table->integer('return_coding');
            $table->integer('year');
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
        Schema::dropIfExists('codings');
    }
}
