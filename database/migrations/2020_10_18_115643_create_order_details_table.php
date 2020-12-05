<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id');
            $table->foreignId('product_id')->nullable();
            $table->foreignId('module_id');
            $table->string('module');
            $table->float('qty');
            $table->float('delivred_qty')->nullable()->default(0);
            $table->float('returned_qty')->nullable()->default(0);
            $table->integer('package_qty')->nullable();
            $table->double('amount', 15, 2);
            $table->string('discount_type')->nullable();
            $table->double('discount', 15, 2)->nullable();
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
        Schema::dropIfExists('order_details');
    }
}
