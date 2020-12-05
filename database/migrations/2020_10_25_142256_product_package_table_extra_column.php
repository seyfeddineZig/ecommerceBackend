<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ProductPackageTableExtraColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //            
        Schema::table('product_packges', function (Blueprint $table) {
            $table->boolean('is_default_package');
            $table->foreignId('customer_category_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::table('product_packges', function (Blueprint $table) {
            $table->dropColumn('is_default_package');
            $table->dropColumn('customer_category_id');
        });
    }
}
