<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $fillable = [
        'image', 'is_activated', 'created_by', 'updated_by' 
    ];

}
