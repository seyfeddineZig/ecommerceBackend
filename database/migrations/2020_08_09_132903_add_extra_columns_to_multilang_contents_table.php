<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExtraColumnsToMultilangContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('multilang_contents', function (Blueprint $table) {
            $table->text('detailed_description')->nullable();
            $table->text('note')->nullable();
            $table->string('page_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('multilang_contents', function (Blueprint $table) {
            $table->dropColumn('detailed_desription');
            $table->dropColumn('note');
            $table->dropColumn('page_title');
            $table->dropColumn('meta_description');
            $table->dropColumn('meta_keywords');
        });
    }
}
