<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    protected $fillable = [
        'name', 'description', 'group'
    ];

    public function user_groups()
    {
        return $this->belongsToMany('App\UserGroup', 'user_group_roles');
    }
    
}
