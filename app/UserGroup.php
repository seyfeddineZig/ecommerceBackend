<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserGroup extends Model
{
    protected $fillable = [
        'name', 'description', 'created_by', 'updated_by'
    ];


    public function roles()
    {
        return $this->belongsToMany('App\UserRole', 'user_group_roles');
    }

    public function hasRole($role)
    {
        return !empty($this->roles()->where('name', $role)->first()) ? true : false;
    }

    public function users()
    {
        return $this->hasMany('App\User', 'user_group_id');
    }

    public function created_by()
    {
        return $this->belongsTo('App\User', 'created_by');
    }

    public function updated_by()
    {
        return $this->belongsTo('App\User', 'updated_by');
    }
}
