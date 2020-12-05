<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMultilangContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('multilang_contents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('module');
            $table->foreignId('module_id');
            $table->foreignId('lang_id');
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
        Schema::dropIfExists('multilang_contents');
    }
}
