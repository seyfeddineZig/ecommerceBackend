<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CustomerFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('phone')->unique();
            $table->string('image')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longtitude')->nullable();
            $table->foreignId('category_id');
            $table->foreignId('activity_id');
            $table->bigInteger('points');
            $table->foreignId('state_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('phone');
            $table->dropColumn('category_id');
            $table->dropColumn('activity_id');
            $table->dropColumn('points');
            $table->dropColumn('state_id');
        });
    }
}
