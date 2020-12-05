<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id');
            $table->foreignId('payment_mode_id');
            $table->integer('payment_deadline_id');
            $table->float('max_debt_id');
            $table->boolean('shipping');
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
        Schema::dropIfExists('customer_contracts');
    }
}
