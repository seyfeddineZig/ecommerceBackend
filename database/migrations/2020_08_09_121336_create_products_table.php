<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('code')->nullable();
            $table->string('bar_code')->nullable();
            $table->string('reference')->nullable();
            $table->float('alert_qty')->nullable();
            $table->boolean('guarantee')->nullable();
            $table->boolean('sell_by_unit');
            $table->foreignId('product_category_id')->nullable();
            $table->foreignId('brand_id')->nullable();
            $table->integer('is_activated');
            $table->string('default_image')->nullable();
            $table->boolean('authtorize_reviews')->nullable();
            $table->text('images')->nullable();
            $table->text('video_url')->nullable();
            $table->text('note')->nullable();
            $table->dateTime('new_mark_expiration')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
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
        Schema::dropIfExists('products');
    }
}
