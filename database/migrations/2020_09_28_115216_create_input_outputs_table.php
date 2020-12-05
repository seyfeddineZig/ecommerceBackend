<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInputOutputsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('input_outputs', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('type');
            $table->foreignId('state_id');
            $table->text('note')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('validated_at')->nullable();
            $table->foreignId('created_by');
            $table->foreignId('updated_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('input_outputs');
    }
}
