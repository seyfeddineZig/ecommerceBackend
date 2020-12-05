<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Lang extends Model
{
    protected $fillable = [
        'full_name', 'short_name', 'dir', 'is_activated', 'default'
    ];

    public function categories()
    {
        return $this->belongsToMany('App\ProductCategory', 'product_category_langs');
    }
}
