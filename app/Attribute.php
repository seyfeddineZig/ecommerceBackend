<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $fillable = [
        'is_activated', 'created_by', 'updated_by' 
    ];
}
