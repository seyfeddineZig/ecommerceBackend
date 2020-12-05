<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id');
            $table->foreignId('customer_id');
            $table->string('code')->nullable();
            $table->double('sub_total', 15, 2);
            $table->double('shipping_fees', 15, 2)->nullable();
            $table->text('shipping_adress')->nullable();
            $table->dateTime('order_date')->nullable();
            $table->string('bill_code')->nullable();
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
        Schema::dropIfExists('orders');
    }
}
