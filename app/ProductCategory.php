<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{

    protected $fillable = [
         'product_category_id','image', 'is_activated', 'created_by', 'updated_by' 
    ];

}
